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
