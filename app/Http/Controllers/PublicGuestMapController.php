<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppBrand;
use App\Http\Services\Appointment\AppointmentRedisService;
use App\Models\Master;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicGuestMapController extends Controller
{
    public function index(): Response
    {
        return $this->renderPage();
    }

    public function showMaster(string $slug, AppointmentRedisService $appointmentRedisService): Response
    {
        $master = Master::with([
            'services.translations',
            'gallery',
            'city',
            'reviews.user',
        ])
            ->where('slug', $slug)
            ->firstOrFail();

        $selectedMaster = $this->serializeSelectedMaster(
            $master,
            $appointmentRedisService->isAvailableFlag($master->id, $master->app),
        );

        return $this->renderPage($selectedMaster);
    }

    private function renderPage(?array $selectedMaster = null): Response
    {
        $brand = config('app.client') instanceof AppBrand
            ? config('app.client')
            : AppBrand::CARBEAT;

        $page = match ($brand) {
            AppBrand::FLOXCITY => 'Floxcity/Public/GuestMap',
            default => 'Carbeat/Public/GuestMap',
        };

        $seo = $selectedMaster !== null
            ? $this->buildSelectedMasterSeo($selectedMaster, $brand)
            : $this->buildGenericSeo($brand);

        return Inertia::render($page, [
            'apiBase' => '/api',
            'mapPath' => route('landing'),
            'initialSelectedMaster' => $selectedMaster,
            'seo' => $seo,
        ]);
    }

    private function serializeSelectedMaster(Master $master, bool $available): array
    {
        $mainPhoto = $master->photo ? Storage::url($master->photo) : $master->main_photo;
        $services = $master->services->map(fn ($service) => [
            'id' => $service->id,
            'name' => $service->name,
            'is_primary' => (int) $service->id === (int) $master->service_id,
        ])->values()->all();

        $reviews = $master->reviews
            ->sortByDesc('id')
            ->map(fn ($review) => [
                'id' => (int) $review->id,
                'rating' => (int) $review->rating,
                'review' => (string) ($review->review ?? ''),
                'user' => $review->user
                    ? ['name' => (string) ($review->user->name ?? '')]
                    : null,
            ])
            ->values()
            ->all();

        $photos = $master->gallery->map(fn ($item) => [
            'id' => $item->id,
            'url' => Storage::url($item->photo),
        ])->values()->all();

        return [
            'id' => (int) $master->id,
            'name' => (string) $master->name,
            'slug' => (string) $master->slug,
            'description' => $master->description,
            'address' => $master->address,
            'city' => optional($master->city)->name,
            'phone' => $master->phone,
            'latitude' => (float) $master->latitude,
            'longitude' => (float) $master->longitude,
            'experience' => $master->experience !== null ? (int) $master->experience : null,
            'services' => $services,
            'photos' => $photos,
            'main_photo' => $mainPhoto,
            'working_hours' => $master->working_hours ?? [],
            'is_claimed' => (bool) $master->is_claimed,
            'claim_link' => $master->is_claimed ? null : $this->buildClaimLink($master),
            'rating' => $master->rating !== null ? (float) $master->rating : 0.0,
            'reviews_count' => (int) ($master->reviews_count ?? $master->reviews->count()),
            'reviews' => $reviews,
            'available' => $available,
        ];
    }

    private function buildGenericSeo(AppBrand $brand): array
    {
        return match ($brand) {
            AppBrand::FLOXCITY => [
                'title' => 'Floxcity Map',
                'description' => 'Find nearby car service stations and auto repair specialists on the Floxcity map.',
                'canonical' => route('landing'),
                'robots' => 'index, follow',
                'ogImage' => url('/og-image.jpg'),
                'structuredData' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => 'Floxcity Map',
                    'url' => route('landing'),
                    'description' => 'Interactive map of nearby car service stations and auto repair specialists.',
                ],
            ],
            default => [
                'title' => 'Carbeat Map',
                'description' => 'Find nearby car service stations and auto repair specialists on the Carbeat map.',
                'canonical' => route('landing'),
                'robots' => 'index, follow',
                'ogImage' => url('/og-image.jpg'),
                'structuredData' => [
                    '@context' => 'https://schema.org',
                    '@type' => 'WebPage',
                    'name' => 'Carbeat Map',
                    'url' => route('landing'),
                    'description' => 'Interactive map of nearby car service stations and auto repair specialists.',
                ],
            ],
        };
    }

    private function buildSelectedMasterSeo(array $selectedMaster, AppBrand $brand): array
    {
        $brandName = $brand === AppBrand::FLOXCITY ? 'Floxcity' : 'Carbeat';
        $address = trim((string) ($selectedMaster['address'] ?? ''));
        $city = trim((string) ($selectedMaster['city'] ?? ''));
        $serviceNames = collect($selectedMaster['services'] ?? [])
            ->pluck('name')
            ->filter()
            ->take(3)
            ->implode(', ');

        $location = trim(implode(', ', array_filter([$city, $address])));
        $descriptionParts = array_filter([
            $location !== '' ? $location : null,
            $serviceNames !== '' ? "Services: {$serviceNames}." : null,
            !empty($selectedMaster['description'])
                ? Str::limit(trim((string) $selectedMaster['description']), 120)
                : null,
        ]);

        $description = Str::limit(
            implode(' ', $descriptionParts) !== ''
                ? "{$selectedMaster['name']} on {$brandName}. ".implode(' ', $descriptionParts)
                : "{$selectedMaster['name']} on {$brandName}. View services, location and reviews on the map.",
            160,
        );

        $canonical = route('public.sto.show', ['slug' => $selectedMaster['slug']]);
        $structuredData = [
            '@context' => 'https://schema.org',
            '@type' => 'AutoRepair',
            'name' => $selectedMaster['name'],
            'url' => $canonical,
            'description' => $description,
            'image' => $selectedMaster['main_photo'] ?: url('/images/default-master.jpg'),
            'telephone' => $selectedMaster['phone'] ?: null,
            'address' => array_filter([
                '@type' => 'PostalAddress',
                'streetAddress' => $address ?: null,
                'addressLocality' => $city ?: null,
            ]),
            'geo' => [
                '@type' => 'GeoCoordinates',
                'latitude' => $selectedMaster['latitude'],
                'longitude' => $selectedMaster['longitude'],
            ],
        ];

        if (($selectedMaster['rating'] ?? 0) > 0 && ($selectedMaster['reviews_count'] ?? 0) > 0) {
            $structuredData['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => number_format((float) $selectedMaster['rating'], 1, '.', ''),
                'reviewCount' => (int) $selectedMaster['reviews_count'],
            ];
        }

        return [
            'title' => "{$selectedMaster['name']} · {$brandName}",
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'ogImage' => $selectedMaster['main_photo'] ?: url('/images/default-master.jpg'),
            'structuredData' => $structuredData,
        ];
    }

    private function buildClaimLink(Master $master): ?string
    {
        if (empty($master->claim_token)) {
            return null;
        }

        $base = rtrim(config('app.claim_base_url'), '/');

        return "{$base}/{$master->claim_token}?master_id={$master->id}";
    }
}
