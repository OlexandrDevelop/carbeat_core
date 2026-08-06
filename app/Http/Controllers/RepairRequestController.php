<?php

namespace App\Http\Controllers;

use App\Http\Requests\SendSmsCodeRequest;
use App\Http\Requests\SubmitRepairRequestRequest;
use App\Http\Services\RepairRequestNotificationService;
use App\Http\Services\SmsService;
use App\Http\Services\UserService;
use App\Models\GeonamesPlace;
use App\Models\RepairRequest;
use App\Models\Service;
use App\Models\User;
use App\Support\CarMakes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Public repair-request form (Carbeat only — see App\Http\Middleware\EnsureCarbeatBrand).
 * A driver fills the form, verifies their phone via SMS OTP, and the request
 * is saved together with a session login (new user created, or an existing
 * one logged in), mirroring App\Http\Controllers\Master\OnboardController.
 */
class RepairRequestController extends Controller
{
    /**
     * Breakdown types offered as the "general issue type" dropdown — a
     * curated subset of the shared Service taxonomy (masters.service_id),
     * since not every Service makes sense as a "breakdown" (e.g. oil_change
     * is a service, not a malfunction).
     */
    private const BREAKDOWN_SERVICE_SLUGS = [
        'engine_repair',
        'transmission_repair',
        'electrical_repair',
        'diagnostics',
        'tire_service',
        'car_body_repair',
        'car_air_conditioning',
        'car_glass',
        'car_alarm',
        'car_audio',
        'car_painting',
    ];

    public function create(): Response
    {
        // The page has its own client-side language switcher (independent of
        // the Accept-Language-driven server locale), so every language's
        // label is sent up front and picked client-side — matching how the
        // rest of the form's UI text works (see guest-map-display-labels.ts).
        $services = Service::whereIn('name', self::BREAKDOWN_SERVICE_SLUGS)
            ->get()
            ->map(fn (Service $service) => [
                'id' => $service->id,
                'labels' => [
                    'en' => $service->translate('en'),
                    'uk' => $service->translate('uk'),
                    'de' => $service->translate('de'),
                ],
            ])
            ->values();

        return Inertia::render('Carbeat/RepairRequest', [
            'carMakes' => CarMakes::LIST,
            'services' => $services,
            'seo' => $this->buildSeo(),
        ]);
    }

    /**
     * @return array{title: string, description: string, canonical: string, robots: string, ogImage: string, structuredData: array<string, mixed>}
     */
    private function buildSeo(): array
    {
        $canonical = route('repair-request.create');

        $structuredData = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'Головна',
                            'item' => route('landing'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => 'Заявка на ремонт',
                            'item' => $canonical,
                        ],
                    ],
                ],
                [
                    '@type' => 'Service',
                    'name' => 'Заявка на ремонт авто',
                    'serviceType' => 'Ремонт та обслуговування автомобілів',
                    'description' => 'Онлайн-заявка на ремонт авто: вкажіть марку, модель, тип поломки — Carbeat підбере перевірений автосервіс поряд з вами.',
                    'provider' => [
                        '@type' => 'Organization',
                        'name' => 'Carbeat',
                        'url' => route('landing'),
                    ],
                    'areaServed' => [
                        '@type' => 'Country',
                        'name' => 'Україна',
                    ],
                    'url' => $canonical,
                ],
            ],
        ];

        return [
            'title' => 'Заявка на ремонт авто онлайн — Carbeat',
            'description' => 'Залиште заявку на ремонт авто на Carbeat: вкажіть марку, модель та тип поломки, підтвердіть номер SMS-кодом — і ми підберемо перевірений автосервіс поряд з вами.',
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'ogImage' => url('/og-image.svg'),
            'structuredData' => $structuredData,
        ];
    }

    /**
     * City autocomplete for the form — a plain SQL `LIKE 'query%'` prefix
     * match against `display_name` (a Ukrainian-Cyrillic name picked out of
     * GeoNames' `alternate_names` at import time, since the raw GeoNames
     * `name` column is a Latin transliteration — see the
     * add_display_name_to_geonames_places_table migration) on the locally
     * imported GeoNames settlement list (App\Models\GeonamesPlace).
     * Replaced a live call to the self-hosted Nominatim instance:
     * Nominatim's search only matches *complete* words (no
     * prefix/autocomplete mode), so "Іван" would never match
     * "Івано-Франківськ" until the whole word was typed. This is instant,
     * needs no external service, and covers every Ukrainian settlement.
     */
    public function citySuggestions(Request $request): JsonResponse
    {
        $query = trim((string) $request->query('q', ''));

        if (mb_strlen($query) < 2) {
            return response()->json(['data' => []]);
        }

        $suggestions = GeonamesPlace::where('country_code', 'UA')
            ->where('feature_class', 'P') // populated places only — excludes stations, rivers, peaks, etc.
            ->where('display_name', 'like', $query.'%')
            ->orderByDesc('population')
            ->limit(8)
            ->get(['display_name', 'latitude', 'longitude'])
            ->map(fn (GeonamesPlace $place) => [
                'name' => $place->display_name,
                'lat' => (float) $place->latitude,
                'lng' => (float) $place->longitude,
            ]);

        return response()->json(['data' => $suggestions]);
    }

    public function requestOtp(SendSmsCodeRequest $request, SmsService $smsService): JsonResponse
    {
        $smsService->generateAndSendCode($request->input('phone'));

        return response()->json(['message' => 'OTP sent']);
    }

    public function verifyAndSubmit(
        SubmitRepairRequestRequest $request,
        SmsService $smsService,
        UserService $userService,
        RepairRequestNotificationService $notificationService
    ): JsonResponse {
        $data = $request->validated();

        if (! $smsService->verifyCode($data['phone'], $data['sms_code'])) {
            return response()->json(['error' => 'Wrong code'], 400);
        }

        $user = $userService->findUserByPhone($data['phone']);
        if (! $user) {
            $user = User::create([
                'phone' => $data['phone'],
                'name' => $data['name'],
            ]);
        }

        if (is_null($user->phone_verified_at)) {
            $user->phone_verified_at = now();
        }
        $user->last_login_at = now();
        $user->save();

        $repairRequest = RepairRequest::create([
            'user_id' => $user->id,
            'service_id' => $data['service_id'] ?? null,
            'city' => $data['city'],
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'car_make' => $data['car_make'],
            'car_model' => $data['car_model'],
            'car_year' => $data['car_year'],
            'description' => $data['description'],
            'phone' => $data['phone'],
            'name' => $data['name'],
        ]);

        $notificationService->notify($repairRequest);

        Auth::guard('web')->login($user, true);

        return response()->json(['status' => 'ok']);
    }
}
