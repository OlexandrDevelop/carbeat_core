<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use App\Models\Country;

class DetectCountry
{
    public function handle(Request $request, Closure $next)
    {
        // Priority: X-Country header (ISO2) -> query param 'country' -> app default
        $code = $request->header('X-Country') ?? $request->query('country');

        if ($code) {
            $code = strtoupper(trim($code));
            $country = Country::where('code', $code)->where('is_active', true)->first();
            if (!$country) {
                return response()->json(['message' => 'Invalid country'], 400);
            }
            // Bind country into container and config for global access
            App::instance('country', $country);
            Config::set('app.country_id', $country->id);
            Config::set('app.country_code', $country->code);

            return $next($request);
        }

        // If no header/query — allow init endpoint to fallback to geo detection or default
        return $next($request);
    }
}

