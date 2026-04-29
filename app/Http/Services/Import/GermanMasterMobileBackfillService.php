<?php

namespace App\Http\Services\Import;

use App\Helpers\PhoneHelper;
use App\Http\Services\Google\GooglePlacesService;
use App\Models\Master;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class GermanMasterMobileBackfillService
{
    public function __construct(
        private readonly GooglePlacesService $googlePlacesService,
        private readonly PhoneHelper $phoneHelper,
    ) {}

    /**
     * @return array{
     *   processed:int,
     *   updated:int,
     *   not_found:int,
     *   not_mobile:int,
     *   failed:int
     * }
     */
    public function run(
        bool $apply = false,
        ?int $limit = null,
        string $mode = 'api',
        ?int $maxApiRequests = null,
        ?callable $onProgress = null,
    ): array
    {
        $stats = [
            'processed' => 0,
            'updated' => 0,
            'not_found' => 0,
            'not_mobile' => 0,
            'failed' => 0,
            'api_requests' => 0,
            'api_budget_exhausted' => 0,
        ];

        $query = Master::query()
            ->where('place_id', 'like', 'auto-werkstatt:%')
            ->whereNotNull('contact_phone')
            ->where('contact_phone', '!=', '');

        if ($limit && $limit > 0) {
            $query->limit($limit);
        }

        /** @var \Illuminate\Support\Collection<int,Master> $masters */
        $masters = $query->get();

        foreach ($masters as $master) {
            $stats['processed']++;

            if (PhoneHelper::isMobile($master->contact_phone ?? '')) {
                $onProgress && $onProgress($stats);
                continue;
            }

            try {
                [$googlePhone, $placeId] = $this->resolvePhone($master, $mode, $stats, $maxApiRequests);

                if (! $googlePhone) {
                    if ($maxApiRequests !== null && $stats['api_requests'] >= $maxApiRequests) {
                        $stats['api_budget_exhausted']++;
                    }
                    $stats['not_found']++;
                    $onProgress && $onProgress($stats);
                    continue;
                }

                $normalizedPhone = $this->phoneHelper->normalize((string) $googlePhone, 'DE');
                if (! PhoneHelper::isMobile($normalizedPhone)) {
                    $stats['not_mobile']++;
                    $onProgress && $onProgress($stats);
                    continue;
                }

                if ($apply) {
                    DB::transaction(function () use ($master, $normalizedPhone, $placeId): void {
                        $extraInfo = $master->extra_info ?? [];
                        if (! is_array($extraInfo)) {
                            $extraInfo = [];
                        }

                        $extraInfo['phone_backfill'] = [
                            'old_contact_phone' => $master->contact_phone,
                            'new_contact_phone' => $normalizedPhone,
                            'google_place_id' => $placeId,
                            'updated_at' => now()->toDateTimeString(),
                        ];

                        $master->contact_phone = $normalizedPhone;
                        $master->extra_info = $extraInfo;
                        $master->save();
                    });
                }

                $stats['updated']++;
            } catch (\Throwable $exception) {
                $stats['failed']++;
                Log::warning('German mobile backfill failed', [
                    'master_id' => $master->id,
                    'message' => $exception->getMessage(),
                ]);
            }

            $onProgress && $onProgress($stats);
        }

        return $stats;
    }

    private function resolvePlaceId(Master $master, array &$stats, ?int $maxApiRequests): ?string
    {
        if ($master->place_id && ! str_starts_with($master->place_id, 'auto-werkstatt:')) {
            return $master->place_id;
        }

        $query = trim(($master->name ?? '') . ' ' . ($master->address ?? ''));
        if ($query === '') {
            return null;
        }

        if ($maxApiRequests !== null && $stats['api_requests'] >= $maxApiRequests) {
            return null;
        }

        $stats['api_requests']++;
        return $this->googlePlacesService->firstPlaceIdByFindPlace($query);
    }

    /**
     * @return array{0:?string,1:?string}
     */
    private function resolvePhone(Master $master, string $mode, array &$stats, ?int $maxApiRequests): array
    {
        if ($mode === 'web') {
            return [$this->resolvePhoneFromWeb($master), null];
        }

        $placeId = $this->resolvePlaceId($master, $stats, $maxApiRequests);
        if (! $placeId) {
            if ($mode === 'hybrid') {
                return [$this->resolvePhoneFromWeb($master), null];
            }
            return [null, null];
        }

        try {
            if ($maxApiRequests !== null && $stats['api_requests'] >= $maxApiRequests) {
                return [null, $placeId];
            }

            $stats['api_requests']++;
            $details = $this->googlePlacesService->detailsPhoneOnly($placeId, 'de');
            $phone = $details['international_phone_number']
                ?? $details['formatted_phone_number']
                ?? null;
            if ($phone) {
                return [(string) $phone, $placeId];
            }
        } catch (\Throwable) {
            // Ignore API fetch issues and fallback if hybrid mode is enabled.
        }

        if ($mode === 'hybrid') {
            return [$this->resolvePhoneFromWeb($master), $placeId];
        }

        return [null, $placeId];
    }

    private function resolvePhoneFromWeb(Master $master): ?string
    {
        $queries = array_filter([
            trim(($master->name ?? '') . ' ' . ($master->address ?? '') . ' google maps'),
            trim(($master->name ?? '') . ' ' . optional($master->city)->name . ' google maps'),
            trim(($master->name ?? '') . ' google maps'),
        ]);

        foreach ($queries as $query) {
            if ($query === '') {
                continue;
            }

            $url = 'https://www.google.com/maps/search/' . rawurlencode($query);

            try {
                $response = \Illuminate\Support\Facades\Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0 Safari/537.36',
                    'Accept-Language' => 'de-DE,de;q=0.9,en;q=0.8',
                ])->timeout(15)->get($url);
            } catch (\Throwable) {
                continue;
            }

            if (! $response->successful()) {
                continue;
            }

            $body = $response->body();
            $candidates = [];

            if (preg_match_all('/\+49[\d\-\s\(\)]{8,}/', $body, $matches)) {
                $candidates = $matches[0];
            }

            foreach ($candidates as $candidate) {
                $normalized = $this->phoneHelper->normalize((string) $candidate, 'DE');
                if (PhoneHelper::isMobile($normalized)) {
                    return $normalized;
                }
            }
        }

        return null;
    }
}
