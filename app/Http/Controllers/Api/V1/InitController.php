<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\CountryDetectionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use App\Models\Country;

class InitController extends Controller
{
    public function init(
        Request $request,
        CountryDetectionService $detector
    ) {
        $code = $request->header('X-Country');

        if ($code) {
            $country = Country::active()
                ->where('code', strtoupper($code))
                ->first();

            if (!$country) {
                return response()->json([
                    'message' => 'Invalid country',
                ], 400);
            }

            $isDetected = false;
        } else {
            $country = $detector->detect($request);
            $isDetected = $country !== null;
        }

        return response()->json([
            'country' => $country ? [
                'id'        => $country->id,
                'code'      => $country->code,
                'name'      => $country->name,
                'phone'     => $country->phone_code,
                'currency'  => $country->currency,
                'locale'    => $country->locale,
                'timezone'  => $country->timezone,
            ] : null,

            'is_detected' => $isDetected,

            'available_countries' => Country::active()
                ->orderBy('name')
                ->get([
                    'code',
                    'name',
                    'phone_code',
                    'currency',
                ]),
        ]);
    }
}
