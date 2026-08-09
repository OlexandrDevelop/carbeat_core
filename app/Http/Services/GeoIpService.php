<?php

namespace App\Http\Services;

use GeoIp2\Database\Reader;
use GeoIp2\Exception\AddressNotFoundException;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeoIpService
{
    private ?Reader $reader = null;

    private bool $readerLoadAttempted = false;

    public function countryCode(?string $ip): ?string
    {
        if (!$ip || !$this->isPublicIp($ip) || !$this->reader()) {
            return null;
        }

        try {
            return $this->reader()->country($ip)->country->isoCode;
        } catch (AddressNotFoundException) {
            return null;
        } catch (Throwable $e) {
            Log::warning('GeoIP country lookup failed', ['ip' => $ip, 'exception' => $e->getMessage()]);

            return null;
        }
    }

    /**
     * Default map view (`['lat' => ..., 'lng' => ..., 'zoom' => ...]`) for the given IP's
     * country, falling back to `geoip.default_center` when the country can't be resolved
     * or has no configured center.
     */
    public function mapViewForIp(?string $ip): array
    {
        $countryCode = $this->countryCode($ip);
        $centers = config('geoip.country_centers', []);

        return ($countryCode && isset($centers[$countryCode]))
            ? $centers[$countryCode]
            : config('geoip.default_center');
    }

    private function isPublicIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
    }

    private function reader(): ?Reader
    {
        if ($this->readerLoadAttempted) {
            return $this->reader;
        }

        $this->readerLoadAttempted = true;
        $path = config('geoip.database_path');

        if (!$path || !is_file($path)) {
            return null;
        }

        try {
            $this->reader = new Reader($path);
        } catch (Throwable $e) {
            Log::warning('GeoIP database failed to load', ['path' => $path, 'exception' => $e->getMessage()]);
            $this->reader = null;
        }

        return $this->reader;
    }
}
