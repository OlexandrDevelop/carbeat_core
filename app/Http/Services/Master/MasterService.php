<?php

namespace App\Http\Services\Master;

use App\Helpers\PhoneHelper;
use App\Helpers\PhotoHelper;
use App\Jobs\CreateMasterThumbnails;
use App\Http\Services\ClientService;
use App\Http\Services\PaginatorService;
use App\Http\Services\TelegramService;
use App\Models\Master;
use App\Models\City;
use App\Models\Service;
use Cocur\Slugify\Slugify;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\LogServiceProvider;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use NotificationChannels\Telegram\TelegramMessage;

class MasterService
{
    protected array $cityCoordinateCache = [];

    protected Master $model;

    protected PaginatorService $paginatorService;

    protected MasterSearchService $masterSearchService;

    public function __construct(Master $master, PaginatorService $paginatorService, MasterSearchService $masterSearchService)
    {
        $this->model = $master;
        $this->paginatorService = $paginatorService;
        $this->masterSearchService = $masterSearchService;
    }

    public function getMastersOnDistance(
        int $page,
        float $lat,
        float $lng,
        float $zoom,
        array $filters
    ): LengthAwarePaginator {
        $perPage = 10000;

        // get masters
        $masters = $this->masterSearchService->getMastersOnDistance($lat, $lng, $zoom, $filters, $perPage, $page);

        // get general count of masters
        $totalMasters = count($masters);

        // create paginator
        return $this->paginatorService->paginate($masters, $totalMasters, $perPage, $page);
    }

    /**
     * @throws Exception
     */
    public function createOrUpdate(array $data): Master
    {
        $photo = $data['photo'] ?? null;
        unset($data['photo']); // handled separately by handlePhoto after save
        // Map phone to contact_phone for persistence
        if (isset($data['phone'])) {
            $data['contact_phone'] = $data['phone'];
            unset($data['phone']);
        }

        $master = Master::updateOrCreate(['contact_phone' => $data['contact_phone'] ?? null], $data);

        $this->handlePhoto($master, $photo);
        $this->assignNearestCity($master);

        if ($master->service_id) {
            $master->services()->syncWithoutDetaching([$master->service_id]);
        }

        return $master;
    }

    /**
     * @throws Exception
     */
    protected function handlePhoto(Master $master, $photo): void
    {
        if ($photo) {
            $oldPhoto = $master->photo;
            if ($oldPhoto) {
                Storage::disk('public')->delete($oldPhoto);
            }

            if (preg_match('/^data:image\/(\w+);base64,/', $photo, $matches)) {
                $extension = $matches[1];
                $photo = base64_decode(substr($photo, strpos($photo, ',') + 1));
                // Persist under flavor directory
                $fl = !empty($master->app) ? (string) $master->app : null;
                $saved = app(\App\Helpers\PhotoHelper::class)->saveDecoded($photo, $extension, $fl);
                if ($saved) $master->update(['photo' => $saved]);
                // Generate a square thumbnail immediately so clients can display it
                try {
                    (new CreateMasterThumbnails([$master->id]))->handle();
                } catch (\Throwable $e) {
                    // Non-fatal if thumbnail creation fails; leave for maintenance command

                }
            } else {
                throw new Exception('The provided photo is not a valid Base64 image.');
            }
        }
    }

    public function updateDetails(Master $master, array $data): void
    {
        if (isset($data['photo'])) {
            $this->handlePhoto($master, $data['photo']);
            unset($data['photo']);
        }
        $master->update($data);
        $this->assignNearestCity($master);

        if (isset($data['service_id']) && $master->service_id) {
            $master->services()->syncWithoutDetaching([$master->service_id]);
        }
    }

    public function addReview(mixed $data): Model
    {
        $master = $this->model::find($data['master_id']);

        return $master->reviews()->create($data);
    }

    public static function generateSlug(Master $master): string
    {
        $specialty = Service::find($master->service_id);
        $specialtyName = $specialty->name ?? '';

        return Slugify::create()->slugify($master->name.' '.$specialtyName);
    }

    public function importFromExternal(int $serviceId, array $data, ClientService $clientService): Master
    {
        $photoBase64 = app(PhotoHelper::class)->downloadAndConvertToBase64($data['main_photo'] ?? '');
        $data['phone'] = app(PhoneHelper::class)->normalize($data['phone'] ?? '');

        // Prefer explicitly provided city_id; fall back to first-word-of-address heuristic
        if (array_key_exists('city_id', $data)) {
            $cityId = $data['city_id'];
        } else {
            $cityId = null;
            $address = (string) ($data['address'] ?? '');
            if ($address !== '') {
                $firstToken = trim(preg_split('/\s+/', $address)[0] ?? '');
                if ($firstToken !== '') {
                    $city = City::firstOrCreate(['name' => $firstToken], ['name' => $firstToken]);
                    $cityId = $city->id;
                }
            }
        }
        $masterData = [
            'user_id' => 1,
            'name' => $data['name'] ?? '',
            'phone' => $data['phone'] ?? '',
            'address' => $data['address'] ?? '',
            'description' => $data['description'] ?? '',
            'latitude' => $data['coordinates']['lat'] ?? null,
            'longitude' => $data['coordinates']['lng'] ?? null,
            'photo' => $photoBase64,
            'service_id' => $serviceId,
            'city_id' => $cityId,
            'place_id' => $data['place_id'] ?? null,
            'rating_google' => $data['rating_google'] ?? null,
            'working_hours' => $data['working_hours'] ?? null,
        ];
        $master = $this->createOrUpdate($masterData);
        if (! empty($data['reviews'])) {
            foreach ($data['reviews'] as $review) {
                $client = $clientService->createOrUpdate([
                    'name' => $review['author'] ?? 'Anonymous',
                    'phone' => $data['phone'] ?? null,
                    'user_id' => 1,
                ]);

                $parsedRating = 0;
                if (! empty($review['rating']) && preg_match('/(\d+)/', $review['rating'], $matches)) {
                    $parsedRating = (int) $matches[1];
                }
                $master->reviews()->firstOrCreate([
                    'review' => $review['text'] ?? '',
                    'rating' => $parsedRating,
                    'user_id' => $client->user_id ?? null,
                    'master_id' => $master->id,
                ]);
            }
        }

        return $master;
    }

    protected function assignNearestCity(Master $master): void
    {
        $lat = $master->latitude;
        $lng = $master->longitude;
        if ($lat === null || $lng === null) {
            return;
        }
        $cityId = $this->resolveNearestCityId((float) $lat, (float) $lng);
        if ($cityId !== null && $master->city_id !== $cityId) {
            $master->city_id = $cityId;
            $master->save();
        }
    }

    protected function resolveNearestCityId(float $lat, float $lng): ?int
    {
        $cities = $this->getCityCoordinateCache();
        if (empty($cities)) {
            return null;
        }
        $nearestId = null;
        $minDistance = PHP_FLOAT_MAX;
        foreach ($cities as $city) {
            $distance = $this->calculateDistance($lat, $lng, $city['latitude'], $city['longitude']);
            if ($distance < $minDistance) {
                $minDistance = $distance;
                $nearestId = $city['id'];
            }
        }
        return $nearestId;
    }

    protected function getCityCoordinateCache(): array
    {
        if (! empty($this->cityCoordinateCache)) {
            return $this->cityCoordinateCache;
        }
        $this->cityCoordinateCache = City::whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->get(['id', 'latitude', 'longitude'])
            ->map(fn ($city) => [
                'id' => $city->id,
                'latitude' => (float) $city->latitude,
                'longitude' => (float) $city->longitude,
            ])
            ->toArray();

        return $this->cityCoordinateCache;
    }

    protected function calculateDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    /**
     * Get master by ID with eager loaded relations.
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public function getMasterById(int $id): Master
    {
        return Master::with(['services.translations', 'gallery', 'reviews.user'])->findOrFail($id);
    }

    /**
     * Update master's additional services (pivot) without touching main service_id.
     *
     * @param  array<int>  $serviceIds
     */
    public function updateServices(Master $master, array $serviceIds): void
    {
        $serviceIds = collect($serviceIds)
            ->map(fn ($v) => (int) $v)
            ->unique()
            ->values();

        // Ensure main service remains represented in pivot for consistency
        if ($master->service_id && !$serviceIds->contains((int) $master->service_id)) {
            $serviceIds->push((int) $master->service_id);
        }

        $master->services()->sync($serviceIds->all());
    }
}
