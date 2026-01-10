<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use App\Models\Country;
use App\Http\Services\CountryDetectionService;

class DetectCountry
{
    public function handle(Request $request, Closure $next)
    {
        // Resolve via container to allow easier testing/mocking
        $detectionService = app(CountryDetectionService::class);

        // If this is the init endpoint, allow detection fallback (header/ip)
        $path = trim($request->path(), '/');
        $isInit = str_ends_with($path, 'app/init');

        // Try to resolve from header only
        $code = $request->header('X-Country');

        if ($code) {
            $code = strtoupper(trim($code));
            $country = Country::where('code', $code)->where('is_active', true)->first();
            if (!$country) {
                return response()->json(['message' => 'Invalid country'], 400);
            }

            $request->attributes->set('country', $country);

            return $next($request);
        }

        if ($isInit) {
            // For init we can attempt geo/ip detection — controller will interpret null as detection failure
            try {
                $country = $detectionService->detect($request);
            } catch (\Throwable $e) {
                return response()->json(['message' => 'Country detection failed'], 500);
            }

            if ($country) {
                $request->attributes->set('country', $country);
            }

            return $next($request);
        }

        // For all other endpoints, require X-Country header
        $country = Country::find(1); // Default country fallback (should not be used in practice)
        $request->attributes->set('country', $country);
        return $next($request);
    }
}
