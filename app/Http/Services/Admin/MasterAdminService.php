<?php

namespace App\Http\Services\Admin;

use App\Helpers\PhoneHelper;
use App\Http\Services\TelegramService;
use App\Models\City;
use App\Models\Master;
use App\Models\Review;
use App\Models\Service;
use Daaner\TurboSMS\Facades\TurboSMS;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MasterAdminService
{
    public function __construct(private readonly PhoneHelper $phoneHelper)
    {
    }

    public function listMasters(array $params): \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Collection
    {
        $query = Master::query()
            ->with(['services.translations', 'user', 'city'])
            ->withAvg('reviews', 'rating')
            ->withCount('gallery');

        $this->applyFilters($query, $params);
        $this->applySorting($query, $params['sort_by'] ?? 'created_at', $params['sort_dir'] ?? 'desc');

        if (!empty($params['no_pagination'])) {
            return $query->get();
        }

        $perPage = min(max((int) ($params['per_page'] ?? 20), 1), 100);
        return $query->paginate($perPage);
    }

    public function getMaster(int $id): Master
    {
        return Master::with(['services.translations', 'user', 'reviews', 'gallery', 'city'])
            ->withAvg('reviews', 'rating')
            ->findOrFail($id);
    }

    public function updateMaster(int $id, array $data): Master
    {
        $master = Master::findOrFail($id);
        $master->fill($data);
        $master->save();

        if (array_key_exists('service_ids', $data)) {
            $master->services()->sync($data['service_ids']);
        }

        return $master->fresh(['services.translations', 'user', 'reviews'])->loadAvg('reviews', 'rating');
    }

    public function deleteMaster(int $id): void
    {
        DB::transaction(function () use ($id) {
            $master = Master::with(['services.translations', 'reviews'])->findOrFail($id);

            if (method_exists($master, 'reviews')) {
                $master->reviews()->delete();
            }
            if (method_exists($master, 'appointments')) {
                $master->appointments()->delete();
            }
            if (method_exists($master, 'galleryPhotos')) {
                $master->galleryPhotos()->delete();
            }

            if (method_exists($master, 'services')) {
                $master->services()->detach();
            }

            $master->delete();
        });
    }

    public function deleteAllMasters(): int
    {
        $deletedCount = 0;
        DB::transaction(function () use (&$deletedCount) {
            Master::query()->chunkById(200, function ($masters) use (&$deletedCount) {
                /** @var Master $master */
                foreach ($masters as $master) {
                    if (method_exists($master, 'reviews')) {
                        $master->reviews()->delete();
                    }
                    if (method_exists($master, 'appointments')) {
                        $master->appointments()->delete();
                    }
                    if (method_exists($master, 'galleryPhotos')) {
                        $master->galleryPhotos()->delete();
                    }
                    if (method_exists($master, 'services')) {
                        $master->services()->detach();
                    }
                    $master->delete();
                    $deletedCount++;
                }
            });
        });
        return $deletedCount;
    }

    public function listServices()
    {
        return Service::query()->orderBy('name')->get(['id', 'name']);
    }

    public function getReviews(int $masterId)
    {
        return Review::where('master_id', $masterId)
            ->with('user:id,name,phone')
            ->orderByDesc('created_at')
            ->get(['id', 'rating', 'review', 'created_at', 'user_id', 'master_id']);
    }

    public function createReview(int $masterId, array $data): Review
    {
        if (empty($data['user_id'])) {
            throw new \InvalidArgumentException('user_id is required');
        }

        $review = Review::create([
            'master_id' => $masterId,
            'user_id' => $data['user_id'],
            'rating' => $data['rating'],
            'review' => $data['review'] ?? null,
        ]);
        return $review->load('user:id,name,phone');
    }

    public function updateReview(int $reviewId, array $data): Review
    {
        $review = Review::findOrFail($reviewId);
        $review->fill($data);
        $review->save();
        return $review->load('user:id,name,phone');
    }

    public function deleteReview(int $reviewId): void
    {
        $review = Review::findOrFail($reviewId);
        $review->delete();
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (! empty($filters['search'])) {
            $search = (string) $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%");
            });
        }

        if (isset($filters['available']) && $filters['available'] !== '') {
            $query->where('available', filter_var($filters['available'], FILTER_VALIDATE_BOOLEAN));
        }

        if (! empty($filters['service_id'])) {
            $serviceId = (int) $filters['service_id'];
            $query->whereHas('services', fn ($q) => $q->where('services.id', $serviceId));
        }

        if (! empty($filters['city_id'])) {
            $cityId = (int) $filters['city_id'];
            $query->where('city_id', $cityId);
        }

        if (! empty($filters['country_code'])) {
            $countryCode = (string) $filters['country_code'];
            $query->whereHas('city', fn($q) => $q->where('country_code', $countryCode));
        }

        if (isset($filters['uses_system']) && $filters['uses_system'] !== '') {
            if (filter_var($filters['uses_system'], FILTER_VALIDATE_BOOLEAN)) {
                $query->where('user_id', '!=', 1);
            } else {
                $query->where('user_id', 1);
            }
        }

        if (isset($filters['sms_invited']) && $filters['sms_invited'] !== '') {
            if (filter_var($filters['sms_invited'], FILTER_VALIDATE_BOOLEAN)) {
                $query->where('sms_invites_sent', '>', 0);
            } else {
                $query->where('sms_invites_sent', 0);
            }
        }

        if (isset($filters['mobile_phone']) && $filters['mobile_phone'] !== '') {
            // Ukrainian mobile: 380/0 + operator code 50,63,66-68,73,91-99
            // German mobile: 49 + 15x / 16x / 17x
            $mobileRegexp = '^((380|0)(50|63|66|67|68|73|91|92|93|94|95|96|97|98|99)[0-9]{7}|49(15|16|17)[0-9]{8,9})$';
            if (filter_var($filters['mobile_phone'], FILTER_VALIDATE_BOOLEAN)) {
                $query->whereRaw("REGEXP_REPLACE(contact_phone, '[^0-9]', '') REGEXP ?", [$mobileRegexp]);
            } else {
                $query->whereRaw("REGEXP_REPLACE(contact_phone, '[^0-9]', '') NOT REGEXP ?", [$mobileRegexp]);
            }
        }
    }

    public function listCities(?string $countryCode = null)
    {
        $q = City::query()->orderBy('name');
        if ($countryCode !== null) {
            $q->where('country_code', $countryCode);
        }
        return $q->get(['id', 'name', 'country_code']);
    }

    private function applySorting(Builder $query, string $sortBy, string $sortDir): void
    {
        $direction = strtolower($sortDir) === 'asc' ? 'asc' : 'desc';
        switch ($sortBy) {
            case 'id':
                $query->orderBy('masters.id', $direction);
                break;
            case 'uses_system':
                $query->orderByRaw('(CASE WHEN user_id != 1 THEN 1 ELSE 0 END) '.$direction);
                break;
            case 'last_login_at':
                $query->leftJoin('users', 'users.id', '=', 'masters.user_id')
                    ->select('masters.*')
                    ->orderBy('users.last_login_at', $direction);
                break;
            case 'city':
                $query->leftJoin('cities', 'cities.id', '=', 'masters.city_id')
                    ->select('masters.*')
                    ->orderBy('cities.name', $direction);
                break;
            case 'rating':
                // Sort by average rating from reviews (use withAvg alias)
                $query->orderBy('reviews_avg_rating', $direction);
                break;
            case 'photos_count':
                // withCount('gallery') alias is gallery_count
                $query->orderBy('gallery_count', $direction);
                break;
            case 'available':
                $query->orderBy('available', $direction);
                break;
            case 'photo':
                // Sort by presence of photo (non-null and non-empty)
                $query->orderByRaw('(CASE WHEN photo IS NOT NULL AND photo != "" THEN 1 ELSE 0 END) '.$direction)
                    ->orderBy('id', 'desc');
                break;
            case 'sms_invites_sent':
            case 'name':
            case 'age':
            case 'created_at':
                $query->orderBy($sortBy, $direction);
                break;
            default:
                $query->orderBy('created_at', 'desc');
        }
    }

    public function sendInvites(array $masterIds, ?string $customMessage = null): array
    {
        $masters = Master::whereIn('id', $masterIds)->get([
            'id',
            'name',
            'slug',
            'contact_phone',
            'claim_token',
        ]);

        $template = $customMessage ?: config('app.master_invite_template');

        $sent = 0;
        $skipped = [];

        foreach ($masters as $master) {
            $phone = $master->phone ?? $master->contact_phone;

            if (empty($phone)) {
                $skipped[] = [
                    'master_id' => $master->id,
                    'reason' => 'missing_phone',
                ];
                continue;
            }

            $normalizedPhone = $this->phoneHelper->normalize($phone);

            if (empty($normalizedPhone) || !$this->phoneHelper->isMobile($normalizedPhone)) {
                $skipped[] = [
                    'master_id' => $master->id,
                    'reason' => 'invalid_phone',
                ];
                continue;
            }

            $this->ensureClaimToken($master);
            $link = $this->buildAppLink($master);
            if (! $link) {
                $skipped[] = [
                    'master_id' => $master->id,
                    'reason' => 'missing_claim_link',
                ];
                continue;
            }

            $message = $template;
            if (str_contains($message, ':link')) {
                $message = str_replace(':link', $link, $message);
            } else {
                $message = trim($message.' '.$link);
            }

            try {
                $testMode = config('turbosms.test_mode', true);
                if ($testMode){
                    $telegramService = new TelegramService();
                    $telegramService->send($message);
                }else {
                    TurboSMS::sendMessages($normalizedPhone, $message);
                }
                $master->increment('sms_invites_sent');
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Failed to send invite SMS', [
                    'master_id' => $master->id,
                    'phone' => $normalizedPhone,
                    'error' => $e->getMessage(),
                ]);
                $skipped[] = [
                    'master_id' => $master->id,
                    'reason' => 'sms_failed',
                ];
            }
        }

        return [
            'sent' => $sent,
            'requested' => count($masterIds),
            'skipped' => $skipped,
        ];
    }

    private function ensureClaimToken(Master $master): void
    {
        if (empty($master->claim_token)) {
            $master->claim_token = Str::random(40);
            $master->save();
        }
    }

    private function buildAppLink(Master $master): ?string
    {
        if (empty($master->claim_token)) {
            return null;
        }

        $base = rtrim(config('app.claim_base_url'), '/');

        return "{$base}/{$master->id}";
    }
}
