<?php

namespace App\Http\Services;

use App\Models\Country;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CountryDetectionService
{
    /**
     * Detect country for the given request.
     * Priority:
     *  1. X-Country header (ISO-2)
     *  2. GeoIP lookup (torann/laravel-geoip + MaxMind) by client IP
     *
     * @param Request $request
     * @return ?Country
     */
    public function detect(Request $request): ?Country
    {
        // 1) Header override only (no query param)
        $code = (string) ($request->header('X-Country') ?: '');
        $code = strtoupper(trim($code));
        if (!empty($code)) {
            $country = Country::where('code', $code)->where('is_active', true)->first();
            return $country ?: null;
        }

        // 2) GeoIP by IP (torann/laravel-geoip)
        try {
            $location = null;
            $clientIp = $request->ip();

            if (class_exists('\\Torann\\GeoIP\\Facades\\GeoIP')) {
                $location = \Torann\GeoIP\Facades\GeoIP::getLocation($clientIp);
            } elseif (class_exists('\\Torann\\GeoIP\\GeoIP')) {
                $location = app(\Torann\GeoIP\GeoIP::class)->getLocation($clientIp);
            } elseif (function_exists('geoip')) {
                $location = call_user_func('geoip', $clientIp);
            }

            if (!empty($location)) {
                $iso = null;
                if (is_array($location)) {
                    $iso = $location['iso_code'] ?? $location['country_code'] ?? $location['country'] ?? null;
                } elseif (is_object($location)) {
                    $iso = $location->iso_code ?? $location->country_code ?? $location->country ?? null;
                }

                if (!empty($iso)) {
                    $iso = strtoupper(substr((string)$iso, 0, 2));
                    $country = Country::where('code', $iso)->where('is_active', true)->first();
                    if ($country) {
                        return $country;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Log failure but do not include IP or headers
            Log::warning('GeoIP detection failed: ' . $e->getMessage());
        }

        // No fallback (config default or first active country) — return null to indicate detection failed
        return null;
    }
}
