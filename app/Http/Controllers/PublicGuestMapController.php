<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppBrand;
use App\Http\Services\Appointment\AppointmentRedisService;
use App\Http\Services\Seo\SeoOverridesService;
use App\Models\City;
use App\Models\Master;
use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicGuestMapController extends Controller
{
    public function __construct(
        private readonly SeoOverridesService $seoOverridesService,
    ) {
    }

    public function index(Request $request): Response
    {
        $seo = $this->buildGenericSeo(
            $this->brand(),
            $request->routeIs('public.guest-map'),
        );

        return $this->renderPage(
            seo: $seo,
            initialMapView: $this->defaultMapView(),
            seoContent: null,
            initialSelectedMaster: null,
            initialServiceId: null,
        );
    }

    public function showMaster(
        string $slug,
        AppointmentRedisService $appointmentRedisService
    ): Response {
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
        $entryKey = "master:{$selectedMaster['slug']}";
        $content = $this->buildMasterSeoContent($selectedMaster);
        $applied = $this->applySeoOverride(
            $entryKey,
            $this->buildSelectedMasterSeo($selectedMaster, $this->brand()),
            $content,
        );

        return $this->renderPage(
            seo: $applied['seo'],
            initialMapView: [
                'center' => [$selectedMaster['latitude'], $selectedMaster['longitude']],
                'zoom' => 14,
            ],
            seoContent: $applied['content'],
            initialSelectedMaster: $selectedMaster,
            initialServiceId: null,
        );
    }

    public function showCity(string $citySlug): Response
    {
        $city = $this->resolveCityBySlug($citySlug);
        $masters = $this->getCityMasters($city);
        $serviceLinks = $this->buildCityServiceLinks($city, $masters);
        $entryKey = 'city:' . Str::slug($city->name);
        $content = $this->buildCitySeoContent($city, $masters, $serviceLinks);
        $applied = $this->applySeoOverride($entryKey, $this->buildCitySeo($city, $masters), $content);

        return $this->renderPage(
            seo: $applied['seo'],
            initialMapView: $this->cityMapView($city),
            seoContent: $applied['content'],
            initialSelectedMaster: null,
            initialServiceId: null,
        );
    }

    public function showCityService(string $citySlug, string $serviceSlug): Response
    {
        $city = $this->resolveCityBySlug($citySlug);
        $service = $this->resolveServiceBySlug($serviceSlug);
        $masters = $this->getCityMasters($city, $service->id);
        abort_if($masters->isEmpty(), 404);

        $serviceLinks = $this->buildCityServiceLinks($city, $this->getCityMasters($city), $service->id);
        $entryKey = 'city_service:' . Str::slug($city->name) . ':' . Str::slug($service->name);
        $content = $this->buildCityServiceSeoContent($city, $service, $masters, $serviceLinks);
        $applied = $this->applySeoOverride($entryKey, $this->buildCityServiceSeo($city, $service, $masters), $content);

        return $this->renderPage(
            seo: $applied['seo'],
            initialMapView: $this->cityMapView($city),
            seoContent: $applied['content'],
            initialSelectedMaster: null,
            initialServiceId: (int) $service->id,
        );
    }

    private function renderPage(
        array $seo,
        array $initialMapView,
        ?array $seoContent,
        ?array $initialSelectedMaster,
        ?int $initialServiceId
    ): Response {
        $brand = $this->brand();
        $page = match ($brand) {
            AppBrand::FLOXCITY => 'Floxcity/Public/GuestMap',
            default => 'Carbeat/Public/GuestMap',
        };

        return Inertia::render($page, [
            'apiBase' => '/api',
            'mapPath' => route('landing'),
            'initialMapView' => $initialMapView,
            'initialSelectedMaster' => $initialSelectedMaster,
            'initialServiceId' => $initialServiceId,
            'seo' => $seo,
            'seoContent' => $seoContent,
        ]);
    }

    private function serializeSelectedMaster(Master $master, bool $available): array
    {
        $mainPhoto = $master->photo ? Storage::url($master->photo) : $master->main_photo;
        $services = $master->services
            ->map(fn ($service) => [
                'id' => $service->id,
                'name' => $service->translate(app()->getLocale()),
                'is_primary' => (int) $service->id === (int) $master->service_id,
            ])
            ->values()
            ->all();

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

        $photos = $master->gallery
            ->map(fn ($item) => [
                'id' => $item->id,
                'url' => Storage::url($item->photo),
            ])
            ->values()
            ->all();

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

    private function serializeMasterCard(Master $master): array
    {
        $serviceNames = $master->services
            ->map(fn ($service) => $service->translate(app()->getLocale()))
            ->take(3)
            ->values()
            ->all();

        return [
            'id' => (int) $master->id,
            'name' => (string) $master->name,
            'slug' => (string) $master->slug,
            'address' => (string) ($master->address ?? ''),
            'city' => optional($master->city)->name,
            'rating' => $master->rating !== null ? (float) $master->rating : 0.0,
            'reviews_count' => (int) ($master->reviews_count ?? 0),
            'service_names' => $serviceNames,
        ];
    }

    private function buildGenericSeo(AppBrand $brand, bool $isTechnicalGuestMapRoute = false): array
    {
        $brandName = $brand === AppBrand::FLOXCITY ? 'Floxcity' : 'Carbeat';

        return [
            'title' => "{$brandName} Map",
            'description' => "Find nearby car service stations and auto repair specialists on the {$brandName} map.",
            'canonical' => route('landing'),
            'robots' => $isTechnicalGuestMapRoute ? 'noindex, follow' : 'index, follow',
            'ogImage' => url('/og-image.jpg'),
            'structuredData' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => "{$brandName} Map",
                'url' => route('landing'),
                'description' => "Interactive map of nearby car service stations and auto repair specialists on {$brandName}.",
            ],
        ];
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
        $faq = [
            [
                'q' => "How to contact {$selectedMaster['name']}?",
                'a' => "Open {$selectedMaster['name']} on the {$brandName} map to call directly, open navigation, and review the listed services before visiting.",
            ],
            [
                'q' => "What services does {$selectedMaster['name']} provide?",
                'a' => $serviceNames !== ''
                    ? "{$selectedMaster['name']} lists services such as {$serviceNames}. Open the profile on the map for the full list."
                    : "Open the profile on the map to review the services currently listed for {$selectedMaster['name']}.",
            ],
        ];

        $schemas = [
            [
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
            ],
            $this->buildBreadcrumbSchema([
                ['label' => 'Map', 'href' => route('landing')],
                ['label' => $selectedMaster['city'] ?: 'STO', 'href' => $selectedMaster['city'] ? route('public.city.show', ['citySlug' => Str::slug((string) $selectedMaster['city'])]) : route('landing')],
                ['label' => $selectedMaster['name'], 'href' => $canonical],
            ]),
            $this->buildFaqSchema($faq),
        ];

        if (($selectedMaster['rating'] ?? 0) > 0 && ($selectedMaster['reviews_count'] ?? 0) > 0) {
            $schemas[0]['aggregateRating'] = [
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
            'structuredData' => $schemas,
        ];
    }

    private function buildCitySeo(City $city, Collection $masters): array
    {
        $brandName = $this->brandName();
        $count = $masters->count();
        $popularServices = $masters
            ->flatMap(fn (Master $master) => $master->services)
            ->unique('id')
            ->take(4)
            ->map(fn (Service $service) => $service->translate(app()->getLocale()))
            ->implode(', ');
        $title = "{$city->name} Car Service Map · {$brandName}";
        $description = Str::limit(
            "Find car service stations and auto repair specialists in {$city->name} on {$brandName}. Browse {$count} listed stations, services, ratings and direct profile links" . ($popularServices !== '' ? " including {$popularServices}." : '.'),
            160,
        );
        $canonical = route('public.city.show', ['citySlug' => Str::slug($city->name)]);
        $faq = [
            [
                'q' => "How to find a car service station in {$city->name}?",
                'a' => "Use the {$brandName} city map for {$city->name} to compare nearby stations, addresses, services and ratings before opening a profile page.",
            ],
            [
                'q' => "Can I browse specific repair categories in {$city->name}?",
                'a' => 'Yes. Open one of the linked service pages for the city to narrow the map to a specific repair or maintenance category.',
            ],
        ];

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'ogImage' => url('/og-image.jpg'),
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $title,
                    'url' => $canonical,
                    'description' => $description,
                ],
                $this->buildItemListSchema($masters, $canonical),
                $this->buildBreadcrumbSchema([
                    ['label' => 'Map', 'href' => route('landing')],
                    ['label' => $city->name, 'href' => $canonical],
                ]),
                $this->buildFaqSchema($faq),
            ],
        ];
    }

    private function buildCityServiceSeo(City $city, Service $service, Collection $masters): array
    {
        $brandName = $this->brandName();
        $serviceName = $service->translate(app()->getLocale());
        $count = $masters->count();
        $title = "{$serviceName} in {$city->name} · {$brandName}";
        $description = Str::limit(
            "Find {$serviceName} providers in {$city->name} on {$brandName}. Compare {$count} listed stations, addresses, ratings and direct profile pages.",
            160,
        );
        $canonical = route('public.city.service.show', [
            'citySlug' => Str::slug($city->name),
            'serviceSlug' => Str::slug($service->name),
        ]);
        $faq = [
            [
                'q' => "How to find {$serviceName} in {$city->name}?",
                'a' => "Use the {$brandName} service page for {$city->name} to compare {$serviceName} providers, ratings, addresses and direct profile links.",
            ],
            [
                'q' => "Can I switch from {$serviceName} to all stations in {$city->name}?",
                'a' => "Yes. Open the city landing page to view all stations in {$city->name}, or keep this page to stay focused on {$serviceName}.",
            ],
        ];

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'ogImage' => url('/og-image.jpg'),
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $title,
                    'url' => $canonical,
                    'description' => $description,
                ],
                $this->buildItemListSchema($masters, $canonical),
                $this->buildBreadcrumbSchema([
                    ['label' => 'Map', 'href' => route('landing')],
                    ['label' => $city->name, 'href' => route('public.city.show', ['citySlug' => Str::slug($city->name)])],
                    ['label' => $serviceName, 'href' => $canonical],
                ]),
                $this->buildFaqSchema($faq),
            ],
        ];
    }

    private function buildMasterSeoContent(array $master): array
    {
        $citySlug = !empty($master['city']) ? Str::slug((string) $master['city']) : null;
        $primaryService = collect($master['services'] ?? [])->firstWhere('is_primary', true);
        $relatedLinks = [];

        if ($citySlug) {
            $relatedLinks[] = [
                'label' => "More stations in {$master['city']}",
                'href' => route('public.city.show', ['citySlug' => $citySlug]),
            ];
        }

        if ($citySlug && !empty($primaryService['name'])) {
            $relatedLinks[] = [
                'label' => "{$primaryService['name']} in {$master['city']}",
                'href' => route('public.city.service.show', [
                    'citySlug' => $citySlug,
                    'serviceSlug' => Str::slug((string) $primaryService['name']),
                ]),
            ];
        }

        return [
            'type' => 'master',
            'title' => $master['name'],
            'intro' => Str::limit((string) ($master['description'] ?? ''), 220),
            'sections' => array_values(array_filter([
                !empty($master['description'])
                    ? [
                        'heading' => "About {$master['name']}",
                        'body' => Str::limit((string) $master['description'], 320),
                    ]
                    : null,
                !empty($master['city']) || !empty($master['address'])
                    ? [
                        'heading' => 'Location',
                        'body' => trim(implode(', ', array_filter([
                            $master['address'] ?? null,
                            $master['city'] ?? null,
                        ]))) . '. Open the marker on the map to build a route and compare nearby stations.',
                    ]
                    : null,
            ])),
            'breadcrumbs' => array_values(array_filter([
                ['label' => 'Map', 'href' => route('landing')],
                $citySlug ? ['label' => (string) $master['city'], 'href' => route('public.city.show', ['citySlug' => $citySlug])] : null,
                ['label' => $master['name'], 'href' => route('public.sto.show', ['slug' => $master['slug']])],
            ])),
            'stats' => array_values(array_filter([
                $master['address'] ? ['label' => 'Address', 'value' => $master['address']] : null,
                !empty($master['rating']) ? ['label' => 'Rating', 'value' => number_format((float) $master['rating'], 1)] : null,
                !empty($master['reviews_count']) ? ['label' => 'Reviews', 'value' => (string) $master['reviews_count']] : null,
            ])),
            'serviceLinks' => collect($master['services'] ?? [])
                ->map(fn ($service) => [
                    'label' => (string) $service['name'],
                    'href' => $citySlug
                        ? route('public.city.service.show', [
                            'citySlug' => $citySlug,
                            'serviceSlug' => Str::slug((string) $service['name']),
                        ])
                        : route('public.sto.show', ['slug' => $master['slug']]),
                ])
                ->values()
                ->all(),
            'topMasters' => [],
            'relatedLinks' => $relatedLinks,
            'faq' => [
                [
                    'q' => "How to contact {$master['name']}?",
                    'a' => 'Use the profile card on the map to call directly, open navigation, or review services and ratings before contacting the station.',
                ],
                [
                    'q' => "Can I compare {$master['name']} with nearby stations?",
                    'a' => 'Yes. Open the city or service landing page from the links below to compare nearby stations and profile pages.',
                ],
            ],
        ];
    }

    private function buildCitySeoContent(City $city, Collection $masters, array $serviceLinks): array
    {
        return [
            'type' => 'city',
            'title' => "Car service stations in {$city->name}",
            'intro' => "Browse nearby stations in {$city->name}, compare services, ratings and direct profile pages, and open each station on the map.",
            'sections' => [
                [
                    'heading' => "Compare stations in {$city->name}",
                    'body' => "This city page groups stations in {$city->name} into one searchable map experience. You can compare profile pages, ratings, service categories and exact map positions without leaving the page.",
                ],
                [
                    'heading' => "Popular repair categories in {$city->name}",
                    'body' => $serviceLinks
                        ? "Use the service links on this page to narrow the city map to specific categories such as " . collect($serviceLinks)->pluck('label')->take(4)->implode(', ') . '.'
                        : "Use the station links below to open profile pages and compare available repair categories in {$city->name}.",
                ],
            ],
            'breadcrumbs' => [
                ['label' => 'Map', 'href' => route('landing')],
                ['label' => $city->name, 'href' => route('public.city.show', ['citySlug' => Str::slug($city->name)])],
            ],
            'stats' => [
                ['label' => 'Stations', 'value' => (string) $masters->count()],
                ['label' => 'Services', 'value' => (string) count($serviceLinks)],
            ],
            'serviceLinks' => $serviceLinks,
            'topMasters' => $masters
                ->take(8)
                ->map(fn (Master $master) => $this->serializeMasterCard($master))
                ->values()
                ->all(),
            'relatedLinks' => $serviceLinks,
            'faq' => [
                [
                    'q' => "How to find a car service station in {$city->name}?",
                    'a' => "Use the map and the station list below to compare addresses, services, ratings and direct profile pages in {$city->name}.",
                ],
                [
                    'q' => "Can I search by service in {$city->name}?",
                    'a' => 'Yes. Use the service links below to open landing pages for specific repair or maintenance categories in the same city.',
                ],
            ],
        ];
    }

    private function buildCityServiceSeoContent(City $city, Service $service, Collection $masters, array $serviceLinks): array
    {
        $serviceName = $service->translate(app()->getLocale());
        $brandName = $this->brandName();

        return [
            'type' => 'city_service',
            'title' => "{$serviceName} in {$city->name}",
            'intro' => "Browse {$serviceName} providers in {$city->name}, compare station profiles, ratings and addresses, and open each station directly on the map.",
            'sections' => [
                [
                    'heading' => "Find {$serviceName} providers in {$city->name}",
                    'body' => "This page focuses the {$brandName} map on {$serviceName} providers in {$city->name}. Use it to compare stations faster than on the full city map.",
                ],
                [
                    'heading' => 'How to use this filtered map',
                    'body' => "Open any listed station profile to review its services, address and rating. If you need a broader overview, use the city page link below to return to all stations in {$city->name}.",
                ],
            ],
            'breadcrumbs' => [
                ['label' => 'Map', 'href' => route('landing')],
                ['label' => $city->name, 'href' => route('public.city.show', ['citySlug' => Str::slug($city->name)])],
                ['label' => $serviceName, 'href' => route('public.city.service.show', [
                    'citySlug' => Str::slug($city->name),
                    'serviceSlug' => Str::slug($service->name),
                ])],
            ],
            'stats' => [
                ['label' => 'Stations', 'value' => (string) $masters->count()],
                ['label' => 'City', 'value' => $city->name],
            ],
            'serviceLinks' => $serviceLinks,
            'topMasters' => $masters
                ->take(10)
                ->map(fn (Master $master) => $this->serializeMasterCard($master))
                ->values()
                ->all(),
            'relatedLinks' => [
                [
                    'label' => "All stations in {$city->name}",
                    'href' => route('public.city.show', ['citySlug' => Str::slug($city->name)]),
                ],
            ],
            'faq' => [
                [
                    'q' => "How to find {$serviceName} in {$city->name}?",
                    'a' => "Use the list below to compare {$serviceName} providers in {$city->name}, then open the chosen station on the map.",
                ],
                [
                    'q' => "Can I open the full city map from this page?",
                    'a' => "Yes. The city landing page shows all stations in {$city->name}, while this page narrows the list to {$serviceName}.",
                ],
            ],
        ];
    }

    private function buildCityServiceLinks(City $city, Collection $masters, ?int $activeServiceId = null): array
    {
        $services = $masters
            ->flatMap(fn (Master $master) => $master->services)
            ->unique('id')
            ->sortBy('name')
            ->values();

        return $services
            ->map(fn (Service $service) => [
                'label' => $service->translate(app()->getLocale()),
                'href' => route('public.city.service.show', [
                    'citySlug' => Str::slug($city->name),
                    'serviceSlug' => Str::slug($service->name),
                ]),
                'active' => $activeServiceId !== null && (int) $service->id === $activeServiceId,
            ])
            ->all();
    }

    private function buildItemListSchema(Collection $masters, string $url): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ItemList',
            'url' => $url,
            'itemListElement' => $masters
                ->take(10)
                ->values()
                ->map(fn (Master $master, int $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => route('public.sto.show', ['slug' => $master->slug]),
                    'name' => $master->name,
                ])
                ->all(),
        ];
    }

    private function buildBreadcrumbSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => collect($items)
                ->values()
                ->map(fn (array $item, int $index) => [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'name' => $item['label'],
                    'item' => $item['href'],
                ])
                ->all(),
        ];
    }

    private function buildFaqSchema(array $items): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => collect($items)
                ->map(fn (array $item) => [
                    '@type' => 'Question',
                    'name' => $item['q'],
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => $item['a'],
                    ],
                ])
                ->values()
                ->all(),
        ];
    }

    private function applySeoOverride(string $entryKey, array $seo, ?array $content): array
    {
        $override = $this->seoOverridesService->get($entryKey, $this->brand());

        if ($override === []) {
            return ['seo' => $seo, 'content' => $content];
        }

        if (!empty($override['title'])) {
            $seo['title'] = (string) $override['title'];
            if (is_array($content)) {
                $content['title'] = (string) $override['title'];
            }
        }

        if (!empty($override['description'])) {
            $seo['description'] = (string) $override['description'];
        }

        if (!empty($override['intro']) && is_array($content)) {
            $content['intro'] = (string) $override['intro'];
        }

        if (!empty($override['sections']) && is_array($content)) {
            $content['sections'] = $override['sections'];
        }

        if (!empty($override['faq']) && is_array($content)) {
            $content['faq'] = $override['faq'];
        }

        if (isset($seo['structuredData'])) {
            $seo['structuredData'] = $this->applyOverrideToStructuredData(
                $seo['structuredData'],
                $seo,
                $content,
            );
        }

        return ['seo' => $seo, 'content' => $content];
    }

    private function applyOverrideToStructuredData(mixed $structuredData, array $seo, ?array $content): mixed
    {
        if (!is_array($structuredData)) {
            return $structuredData;
        }

        $items = array_is_list($structuredData) ? $structuredData : [$structuredData];

        foreach ($items as &$item) {
            if (!is_array($item)) {
                continue;
            }

            if (($item['@type'] ?? null) === 'FAQPage' && is_array($content['faq'] ?? null)) {
                $item = $this->buildFaqSchema($content['faq']);
                continue;
            }

            if (isset($item['description'])) {
                $item['description'] = $seo['description'];
            }

            if (isset($item['name']) && in_array($item['@type'] ?? '', ['AutoRepair', 'CollectionPage', 'WebPage'], true)) {
                $item['name'] = $seo['title'];
            }
        }

        return array_is_list($structuredData) ? $items : ($items[0] ?? $structuredData);
    }

    private function getCityMasters(City $city, ?int $serviceId = null): Collection
    {
        return Master::with(['services.translations', 'city'])
            ->where('city_id', $city->id)
            ->when($serviceId !== null, function ($query) use ($serviceId) {
                $query->where(function ($subQuery) use ($serviceId) {
                    $subQuery->where('service_id', $serviceId)
                        ->orWhereHas('services', fn ($services) => $services->where('services.id', $serviceId));
                });
            })
            ->orderByDesc('rating')
            ->orderByDesc('reviews_count')
            ->orderBy('name')
            ->limit(60)
            ->get();
    }

    private function resolveCityBySlug(string $citySlug): City
    {
        return City::query()
            ->whereHas('masters')
            ->get()
            ->first(fn (City $city) => Str::slug($city->name) === $citySlug)
            ?? abort(404);
    }

    private function resolveServiceBySlug(string $serviceSlug): Service
    {
        return Service::with('translations')
            ->get()
            ->first(function (Service $service) use ($serviceSlug) {
                if (Str::slug($service->name) === $serviceSlug) {
                    return true;
                }

                return $service->translations
                    ->contains(fn ($translation) => Str::slug((string) $translation->name) === $serviceSlug);
            })
            ?? abort(404);
    }

    private function cityMapView(City $city): array
    {
        if ($city->latitude !== null && $city->longitude !== null) {
            return [
                'center' => [(float) $city->latitude, (float) $city->longitude],
                'zoom' => 12,
            ];
        }

        return $this->defaultMapView();
    }

    private function defaultMapView(): array
    {
        return [
            'center' => [50.4501, 30.5234],
            'zoom' => 11,
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

    private function brand(): AppBrand
    {
        return config('app.client') instanceof AppBrand
            ? config('app.client')
            : AppBrand::CARBEAT;
    }

    private function brandName(): string
    {
        return $this->brand() === AppBrand::FLOXCITY ? 'Floxcity' : 'Carbeat';
    }
}
