<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\AppBrand;
use App\Http\Services\Appointment\AppointmentRedisService;
use App\Http\Services\Seo\SeoOverridesService;
use App\Http\Services\Seo\UkrainianSeoCopyGenerator;
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
        private readonly UkrainianSeoCopyGenerator $seoCopyGenerator,
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
        $content = $this->buildMasterSeoContent($selectedMaster, $this->brand());
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
        $copy = $this->buildCityCopy($city, $masters);
        $entryKey = 'city:' . Str::slug($city->name);
        $content = $this->buildCitySeoContent($copy, $city, $masters, $serviceLinks);
        $applied = $this->applySeoOverride($entryKey, $this->buildCitySeo($copy, $city, $masters), $content);

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
        $serviceName = $service->translate(app()->getLocale());
        $copy = $this->seoCopyGenerator->cityServiceSeo($city, $service, $serviceName, $masters->count(), $this->brand(), $this->brandName());
        $entryKey = 'city_service:' . Str::slug($city->name) . ':' . Str::slug($service->name);
        $content = $this->buildCityServiceSeoContent($copy, $city, $service, $masters, $serviceLinks);
        $applied = $this->applySeoOverride($entryKey, $this->buildCityServiceSeo($copy, $city, $service, $masters), $content);

        return $this->renderPage(
            seo: $applied['seo'],
            initialMapView: $this->cityMapView($city),
            seoContent: $applied['content'],
            initialSelectedMaster: null,
            initialServiceId: (int) $service->id,
        );
    }

    public function showService(string $serviceSlug): Response
    {
        $service = $this->resolveServiceBySlug($serviceSlug);
        $masters = $this->getServiceMasters($service->id);
        abort_if($masters->isEmpty(), 404);

        $cityLinks = $this->buildServiceCityLinks($service, $masters);
        $serviceName = $service->translate(app()->getLocale());
        $copy = $this->seoCopyGenerator->serviceSeo($service, $serviceName, $masters->count(), $this->brand(), $this->brandName());
        $entryKey = 'service:' . Str::slug($service->name);
        $content = $this->buildServiceSeoContent($copy, $service, $masters, $cityLinks);
        $applied = $this->applySeoOverride($entryKey, $this->buildServiceSeo($copy, $service, $masters), $content);

        return $this->renderPage(
            seo: $applied['seo'],
            initialMapView: $this->defaultMapView(),
            seoContent: $applied['content'],
            initialSelectedMaster: null,
            initialServiceId: (int) $service->id,
        );
    }

    private function buildCityCopy(City $city, Collection $masters): array
    {
        $popularServiceNames = $masters
            ->flatMap(fn (Master $master) => $master->services)
            ->unique('id')
            ->take(4)
            ->map(fn (Service $service) => $service->translate(app()->getLocale()))
            ->values()
            ->all();

        return $this->seoCopyGenerator->citySeo($city, $masters->count(), $popularServiceNames, $this->brand(), $this->brandName());
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
            'profilePathPrefix' => $this->brand() === AppBrand::FLOXCITY ? '/salon' : '/sto',
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
            'rating' => $master->reviews_avg_rating !== null
                ? (float) $master->reviews_avg_rating
                : ($master->rating_google !== null ? (float) $master->rating_google : 0.0),
            'reviews_count' => (int) ($master->reviews_count ?? 0),
            'service_names' => $serviceNames,
        ];
    }

    private function buildGenericSeo(AppBrand $brand, bool $isTechnicalGuestMapRoute = false): array
    {
        $brandName = $brand === AppBrand::FLOXCITY ? 'Floxcity' : 'Carbeat';
        $vertical = $this->seoCopyGenerator->vertical($brand);

        $title = $brand === AppBrand::FLOXCITY
            ? "{$brandName} — карта салонів краси та майстрів поруч"
            : "{$brandName} — карта СТО та автосервісів поруч";
        $description = "Знайдіть {$vertical['placeNounMid']} поруч на карті {$brandName}. Порівняйте рейтинги, послуги та відкривайте профілі напряму.";

        return [
            'title' => $title,
            'description' => $description,
            'canonical' => route('landing'),
            'robots' => $isTechnicalGuestMapRoute ? 'noindex, follow' : 'index, follow',
            'ogImage' => url('/og-image.svg'),
            'structuredData' => [
                '@context' => 'https://schema.org',
                '@type' => 'WebPage',
                'name' => $title,
                'url' => route('landing'),
                'description' => $description,
            ],
        ];
    }

    private function buildSelectedMasterSeo(array $selectedMaster, AppBrand $brand): array
    {
        $brandName = $brand === AppBrand::FLOXCITY ? 'Floxcity' : 'Carbeat';
        $vertical = $this->seoCopyGenerator->vertical($brand);
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
            $serviceNames !== '' ? "Послуги: {$serviceNames}." : null,
            !empty($selectedMaster['description'])
                ? Str::limit(trim((string) $selectedMaster['description']), 120)
                : null,
        ]);

        $description = Str::limit(
            implode(' ', $descriptionParts) !== ''
                ? "{$selectedMaster['name']} на {$brandName}. " . implode(' ', $descriptionParts)
                : "{$selectedMaster['name']} на {$brandName}. Послуги, розташування та відгуки — на карті.",
            160,
        );

        $canonical = $this->profileUrl((string) $selectedMaster['slug']);
        $faq = [
            [
                'q' => "Як зв'язатися з {$selectedMaster['name']}?",
                'a' => "Відкрийте {$selectedMaster['name']} на карті {$brandName}, щоб зателефонувати напряму, відкрити навігацію та переглянути перелік послуг перед візитом.",
            ],
            [
                'q' => "Які послуги надає {$selectedMaster['name']}?",
                'a' => $serviceNames !== ''
                    ? "{$selectedMaster['name']} пропонує послуги: {$serviceNames}. Відкрийте профіль на карті, щоб побачити повний список."
                    : "Відкрийте профіль на карті, щоб переглянути актуальний перелік послуг {$selectedMaster['name']}.",
            ],
        ];

        $schemas = [
            [
                '@context' => 'https://schema.org',
                '@type' => $vertical['schemaType'],
                'name' => $selectedMaster['name'],
                'url' => $canonical,
                'description' => $description,
                'image' => $selectedMaster['main_photo'] ?: url('/images/default-master.svg'),
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
                ['label' => 'Мапа', 'href' => route('landing')],
                ['label' => $selectedMaster['city'] ?: ucfirst($vertical['placeNounSingular']), 'href' => $selectedMaster['city'] ? route('public.city.show', ['citySlug' => Str::slug((string) $selectedMaster['city'])]) : route('landing')],
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
            'ogImage' => $selectedMaster['main_photo'] ?: url('/images/default-master.svg'),
            'structuredData' => $schemas,
        ];
    }

    private function buildCitySeo(array $copy, City $city, Collection $masters): array
    {
        $canonical = route('public.city.show', ['citySlug' => Str::slug($city->name)]);

        return [
            'title' => $copy['metaTitle'],
            'description' => $copy['description'],
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'ogImage' => url('/og-image.svg'),
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $copy['metaTitle'],
                    'url' => $canonical,
                    'description' => $copy['description'],
                ],
                $this->buildItemListSchema($masters, $canonical),
                $this->buildBreadcrumbSchema([
                    ['label' => 'Мапа', 'href' => route('landing')],
                    ['label' => $city->name, 'href' => $canonical],
                ]),
                $this->buildFaqSchema($copy['faq']),
            ],
        ];
    }

    private function buildCityServiceSeo(array $copy, City $city, Service $service, Collection $masters): array
    {
        $canonical = route('public.city.service.show', [
            'citySlug' => Str::slug($city->name),
            'serviceSlug' => Str::slug($service->name),
        ]);

        return [
            'title' => $copy['metaTitle'],
            'description' => $copy['description'],
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'ogImage' => url('/og-image.svg'),
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $copy['metaTitle'],
                    'url' => $canonical,
                    'description' => $copy['description'],
                ],
                $this->buildItemListSchema($masters, $canonical),
                $this->buildBreadcrumbSchema([
                    ['label' => 'Мапа', 'href' => route('landing')],
                    ['label' => $city->name, 'href' => route('public.city.show', ['citySlug' => Str::slug($city->name)])],
                    ['label' => $service->translate(app()->getLocale()), 'href' => $canonical],
                ]),
                $this->buildFaqSchema($copy['faq']),
            ],
        ];
    }

    private function buildServiceSeo(array $copy, Service $service, Collection $masters): array
    {
        $canonical = route('public.service.show', ['serviceSlug' => Str::slug($service->name)]);

        return [
            'title' => $copy['metaTitle'],
            'description' => $copy['description'],
            'canonical' => $canonical,
            'robots' => 'index, follow',
            'ogImage' => url('/og-image.svg'),
            'structuredData' => [
                [
                    '@context' => 'https://schema.org',
                    '@type' => 'CollectionPage',
                    'name' => $copy['metaTitle'],
                    'url' => $canonical,
                    'description' => $copy['description'],
                ],
                $this->buildItemListSchema($masters, $canonical),
                $this->buildBreadcrumbSchema([
                    ['label' => 'Мапа', 'href' => route('landing')],
                    ['label' => $service->translate(app()->getLocale()), 'href' => $canonical],
                ]),
                $this->buildFaqSchema($copy['faq']),
            ],
        ];
    }

    private function buildMasterSeoContent(array $master, AppBrand $brand): array
    {
        $vertical = $this->seoCopyGenerator->vertical($brand);
        $citySlug = !empty($master['city']) ? Str::slug((string) $master['city']) : null;
        $primaryService = collect($master['services'] ?? [])->firstWhere('is_primary', true);
        $relatedLinks = [];

        if ($citySlug) {
            $relatedLinks[] = [
                'label' => "Більше {$vertical['entityMany']} у місті {$master['city']}",
                'href' => route('public.city.show', ['citySlug' => $citySlug]),
            ];
        }

        if ($citySlug && !empty($primaryService['name'])) {
            $relatedLinks[] = [
                'label' => "{$primaryService['name']} у місті {$master['city']}",
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
                        'heading' => "Про {$master['name']}",
                        'body' => Str::limit((string) $master['description'], 320),
                    ]
                    : null,
                !empty($master['city']) || !empty($master['address'])
                    ? [
                        'heading' => 'Розташування',
                        'body' => trim(implode(', ', array_filter([
                            $master['address'] ?? null,
                            $master['city'] ?? null,
                        ]))) . ". Відкрийте маркер на карті, щоб прокласти маршрут і порівняти сусідні {$vertical['entityFew']}.",
                    ]
                    : null,
            ])),
            'breadcrumbs' => array_values(array_filter([
                ['label' => 'Мапа', 'href' => route('landing')],
                $citySlug ? ['label' => (string) $master['city'], 'href' => route('public.city.show', ['citySlug' => $citySlug])] : null,
                ['label' => $master['name'], 'href' => $this->profileUrl((string) $master['slug'])],
            ])),
            'stats' => array_values(array_filter([
                $master['address'] ? ['label' => 'Адреса', 'value' => $master['address']] : null,
                !empty($master['rating']) ? ['label' => 'Рейтинг', 'value' => number_format((float) $master['rating'], 1)] : null,
                !empty($master['reviews_count']) ? ['label' => 'Відгуки', 'value' => (string) $master['reviews_count']] : null,
            ])),
            'serviceLinks' => collect($master['services'] ?? [])
                ->map(fn ($service) => [
                    'label' => (string) $service['name'],
                    'href' => $citySlug
                        ? route('public.city.service.show', [
                            'citySlug' => $citySlug,
                            'serviceSlug' => Str::slug((string) $service['name']),
                        ])
                        : $this->profileUrl((string) $master['slug']),
                ])
                ->values()
                ->all(),
            'topMasters' => [],
            'relatedLinks' => $relatedLinks,
            'faq' => [
                [
                    'q' => "Як зв'язатися з {$master['name']}?",
                    'a' => 'Скористайтеся карткою профілю на карті, щоб зателефонувати напряму, відкрити навігацію або переглянути послуги й рейтинг перед візитом.',
                ],
                [
                    'q' => "Чи можна порівняти {$master['name']} з іншими поблизу?",
                    'a' => 'Так. Відкрийте сторінку міста або послуги за посиланнями нижче, щоб порівняти сусідні варіанти та профілі.',
                ],
            ],
        ];
    }

    private function buildCitySeoContent(array $copy, City $city, Collection $masters, array $serviceLinks): array
    {
        return [
            'type' => 'city',
            'title' => $copy['title'],
            'intro' => $copy['intro'],
            'sections' => $copy['sections'],
            'breadcrumbs' => [
                ['label' => 'Мапа', 'href' => route('landing')],
                ['label' => $city->name, 'href' => route('public.city.show', ['citySlug' => Str::slug($city->name)])],
            ],
            'stats' => [
                ['label' => 'Станцій', 'value' => (string) $masters->count()],
                ['label' => 'Послуг', 'value' => (string) count($serviceLinks)],
            ],
            'serviceLinks' => $serviceLinks,
            'topMasters' => $masters
                ->take(8)
                ->map(fn (Master $master) => $this->serializeMasterCard($master))
                ->values()
                ->all(),
            'relatedLinks' => $serviceLinks,
            'faq' => $copy['faq'],
        ];
    }

    private function buildCityServiceSeoContent(array $copy, City $city, Service $service, Collection $masters, array $serviceLinks): array
    {
        $serviceName = $service->translate(app()->getLocale());

        return [
            'type' => 'city_service',
            'title' => $copy['title'],
            'intro' => $copy['intro'],
            'sections' => $copy['sections'],
            'breadcrumbs' => [
                ['label' => 'Мапа', 'href' => route('landing')],
                ['label' => $city->name, 'href' => route('public.city.show', ['citySlug' => Str::slug($city->name)])],
                ['label' => $serviceName, 'href' => route('public.city.service.show', [
                    'citySlug' => Str::slug($city->name),
                    'serviceSlug' => Str::slug($service->name),
                ])],
            ],
            'stats' => [
                ['label' => 'Станцій', 'value' => (string) $masters->count()],
                ['label' => 'Місто', 'value' => $city->name],
            ],
            'serviceLinks' => $serviceLinks,
            'topMasters' => $masters
                ->take(10)
                ->map(fn (Master $master) => $this->serializeMasterCard($master))
                ->values()
                ->all(),
            'relatedLinks' => [
                [
                    'label' => "Усі станції міста {$city->name}",
                    'href' => route('public.city.show', ['citySlug' => Str::slug($city->name)]),
                ],
            ],
            'faq' => $copy['faq'],
        ];
    }

    private function buildServiceSeoContent(array $copy, Service $service, Collection $masters, array $cityLinks): array
    {
        return [
            'type' => 'service',
            'title' => $copy['title'],
            'intro' => $copy['intro'],
            'sections' => $copy['sections'],
            'breadcrumbs' => [
                ['label' => 'Мапа', 'href' => route('landing')],
                ['label' => $service->translate(app()->getLocale()), 'href' => route('public.service.show', ['serviceSlug' => Str::slug($service->name)])],
            ],
            'stats' => [
                ['label' => 'Станцій', 'value' => (string) $masters->count()],
                ['label' => 'Міст', 'value' => (string) count($cityLinks)],
            ],
            'serviceLinks' => $cityLinks,
            'topMasters' => $masters
                ->take(10)
                ->map(fn (Master $master) => $this->serializeMasterCard($master))
                ->values()
                ->all(),
            'relatedLinks' => $cityLinks,
            'faq' => $copy['faq'],
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

    private function buildServiceCityLinks(Service $service, Collection $masters): array
    {
        $serviceSlug = Str::slug($service->name);

        $cityNames = [];
        foreach ($masters as $master) {
            $cityName = optional($master->city)->name;
            if ($cityName) {
                $cityNames[$cityName] = $cityName;
            }
        }
        ksort($cityNames, SORT_NATURAL | SORT_FLAG_CASE);

        return collect($cityNames)
            ->map(fn (string $cityName) => [
                'label' => $cityName,
                'href' => route('public.city.service.show', [
                    'citySlug' => Str::slug($cityName),
                    'serviceSlug' => $serviceSlug,
                ]),
            ])
            ->values()
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
                    'url' => $this->profileUrl((string) $master->slug),
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
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where('city_id', $city->id)
            ->when($serviceId !== null, function ($query) use ($serviceId) {
                $query->where(function ($subQuery) use ($serviceId) {
                    $subQuery->where('service_id', $serviceId)
                        ->orWhereHas('services', fn ($services) => $services->where('services.id', $serviceId));
                });
            })
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->orderByDesc('rating_google')
            ->orderBy('name')
            ->limit(60)
            ->get();
    }

    private function getServiceMasters(int $serviceId): Collection
    {
        return Master::with(['services.translations', 'city'])
            ->withCount('reviews')
            ->withAvg('reviews', 'rating')
            ->where(function ($subQuery) use ($serviceId) {
                $subQuery->where('service_id', $serviceId)
                    ->orWhereHas('services', fn ($services) => $services->where('services.id', $serviceId));
            })
            ->orderByDesc('reviews_avg_rating')
            ->orderByDesc('reviews_count')
            ->orderByDesc('rating_google')
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

    private function profileUrl(string $slug, ?AppBrand $brand = null): string
    {
        return route($this->profileRouteName($brand), ['slug' => $slug]);
    }

    private function profileRouteName(?AppBrand $brand = null): string
    {
        $brand ??= $this->brand();

        return $brand === AppBrand::FLOXCITY
            ? 'public.salon.show'
            : 'public.sto.show';
    }
}
