<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Models\Country;
use App\Models\City;
use App\Models\Service;

class InitController extends Controller
{
    public function init(Request $request)
    {
        // Allow client to request selection via header or query param
        $code = $request->header('X-Country') ?? $request->query('country');

        if ($code) {
            $code = strtoupper(trim($code));
            $country = Country::where('code', $code)->where('is_active', true)->first();
            if (!$country) {
                return response()->json(['message' => 'Invalid country'], 400);
            }
        } else {
            // Fallback: pick default country if not provided (use config or first active)
            $country = Country::where('is_active', true)->first();
        }

        // Bind into container for the rest of the request processing
        App::instance('country', $country);

        // Return the initial payload for app
        $cities = City::where('country_id', $country->id)->orderBy('name')->get(['id','name']);
        $services = Service::where('country_id', $country->id)->orderBy('name')->get(['id','name']);

        return response()->json([
            'country' => [
                'id' => $country->id,
                'code' => $country->code,
                'name' => $country->name,
                'locale' => $country->locale,
                'currency' => $country->currency,
            ],
            'cities' => $cities,
            'services' => $services,
        ]);
    }
}

