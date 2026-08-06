<?php

namespace App\Http\Services\Import;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NominatimGeocoder
{
    /**
     * Reverse-geocode coordinates against a self-hosted Nominatim instance to get the
     * current official Ukrainian city/street name (source sites like vse-sto serve
     * Russian text, which can also be outdated after street renamings).
     *
     * @return array{city: ?string, road: ?string, house_number: ?string}|null
     */
    public function reverse(float $lat, float $lng): ?array
    {
        $baseUrl = config('services.nominatim.url');
        if (empty($baseUrl)) {
            return null;
        }

        $cacheKey = 'nominatim:reverse:'.round($lat, 5).','.round($lng, 5);
        $cached = Cache::store('redis')->get($cacheKey);
        if ($cached) {
            return $cached;
        }

        $result = $this->fetch($baseUrl, $lat, $lng);
        if ($result) {
            Cache::store('redis')->put($cacheKey, $result, now()->addDays(30));
        }

        return $result;
    }

    /**
     * Forward-search Ukrainian city/town/village names against the same
     * self-hosted Nominatim instance, for the repair-request form's city
     * autocomplete (App\Http\Controllers\RepairRequestController).
     *
     * @return array<int, array{name: string, lat: float, lng: float}>
     */
    public function search(string $query): array
    {
        $query = trim($query);
        $baseUrl = config('services.nominatim.url');
        if (empty($baseUrl) || mb_strlen($query) < 2) {
            return [];
        }

        $cacheKey = 'nominatim:search:'.mb_strtolower($query);
        $cached = Cache::store('redis')->get($cacheKey);
        if ($cached !== null) {
            return $cached;
        }

        $results = $this->fetchSearch($baseUrl, $query);
        Cache::store('redis')->put($cacheKey, $results, now()->addDay());

        return $results;
    }

    /**
     * @return array<int, array{name: string, lat: float, lng: float}>
     */
    private function fetchSearch(string $baseUrl, string $query): array
    {
        try {
            $response = Http::timeout(10)->retry(2, 300)->get(rtrim($baseUrl, '/').'/search', [
                'q' => $query,
                'format' => 'jsonv2',
                'countrycodes' => 'ua',
                'accept-language' => 'uk',
                'addressdetails' => 1,
                'limit' => 10,
            ]);
        } catch (\Throwable $e) {
            Log::warning('Nominatim city search request failed', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return [];
        }

        if (! $response->successful()) {
            return [];
        }

        // Nominatim returns every kind of place (bars, streets, admin
        // regions...) for a free-text query — keep only actual
        // city/town/village-level settlements.
        $cityTypes = ['city', 'town', 'village', 'municipality'];

        return collect($response->json() ?? [])
            ->filter(fn (array $item) => in_array($item['addresstype'] ?? null, $cityTypes, true))
            ->map(fn (array $item) => [
                'name' => (string) ($item['name'] ?? $item['display_name']),
                'lat' => (float) $item['lat'],
                'lng' => (float) $item['lon'],
            ])
            ->unique('name')
            ->values()
            ->all();
    }

    /**
     * @return array{city: ?string, road: ?string, house_number: ?string}|null
     */
    private function fetch(string $baseUrl, float $lat, float $lng): ?array
    {
        try {
            $response = Http::timeout(10)->retry(2, 300)->get(rtrim($baseUrl, '/').'/reverse', [
                'lat' => $lat,
                'lon' => $lng,
                'format' => 'jsonv2',
                'accept-language' => 'uk',
            ]);
        } catch (\Throwable $e) {
            Log::warning('Nominatim reverse geocode request failed', [
                'lat' => $lat,
                'lng' => $lng,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        if (! $response->successful()) {
            return null;
        }

        $address = $response->json('address') ?? [];
        if (empty($address)) {
            return null;
        }

        return [
            'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? $address['municipality'] ?? null,
            'road' => $address['road'] ?? null,
            'house_number' => $address['house_number'] ?? null,
        ];
    }
}
