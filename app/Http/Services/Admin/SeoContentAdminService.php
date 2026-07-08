<?php

declare(strict_types=1);

namespace App\Http\Services\Admin;

use App\Enums\AppBrand;
use App\Http\Services\Seo\SeoOverridesService;
use App\Http\Services\Seo\UkrainianSeoCopyGenerator;
use App\Models\City;
use App\Models\Master;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SeoContentAdminService
{
    public function __construct(
        private readonly SeoOverridesService $overridesService,
        private readonly UkrainianSeoCopyGenerator $copyGenerator,
    ) {
    }

    public function list(array $filters = []): array
    {
        $type = (string) ($filters['type'] ?? 'all');
        $search = Str::lower(trim((string) ($filters['search'] ?? '')));
        $entries = collect();

        if ($type === 'all' || $type === 'master') {
            $entries = $entries->merge($this->masterEntries($search));
        }

        if ($type === 'all' || $type === 'city') {
            $entries = $entries->merge($this->cityEntries($search));
        }

        if ($type === 'all' || $type === 'city_service') {
            $entries = $entries->merge($this->cityServiceEntries($search));
        }

        if ($type === 'all' || $type === 'service') {
            $entries = $entries->merge($this->serviceEntries($search));
        }

        return [
            'entries' => $entries
                ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
                ->values()
                ->take(150)
                ->all(),
        ];
    }

    public function save(array $payload): array
    {
        $key = (string) $payload['key'];
        $override = $this->overridesService->put($key, $payload, $this->brand());

        return [
            'key' => $key,
            'override' => $override,
        ];
    }

    private function masterEntries(string $search): Collection
    {
        return Master::with(['city', 'services.translations'])
            ->orderBy('name')
            ->get()
            ->filter(function (Master $master) use ($search) {
                if ($search === '') {
                    return true;
                }

                return Str::contains(
                    Str::lower($master->name . ' ' . ($master->slug ?? '') . ' ' . optional($master->city)->name),
                    $search,
                );
            })
            ->map(function (Master $master) {
                $cityName = optional($master->city)->name;
                $services = $master->services
                    ->map(fn (Service $service) => $service->translate(app()->getLocale()))
                    ->take(3)
                    ->values()
                    ->all();

                $default = [
                    'title' => "{$master->name} · " . $this->brandName(),
                    'description' => Str::limit(
                        "{$master->name}" .
                        ($cityName ? " у місті {$cityName}" : '') .
                        '. Послуги, адреса та відгуки — на карті.',
                        160,
                    ),
                    'intro' => Str::limit((string) ($master->description ?? ''), 220),
                    'sections' => array_values(array_filter([
                        !empty($master->description) ? [
                            'heading' => "Про {$master->name}",
                            'body' => Str::limit((string) $master->description, 320),
                        ] : null,
                        ($master->address || $cityName) ? [
                            'heading' => 'Розташування',
                            'body' => trim(implode(', ', array_filter([$master->address, $cityName]))) . '. Відкрийте маркер на карті, щоб прокласти маршрут і порівняти сусідні станції.',
                        ] : null,
                    ])),
                    'faq' => [
                        [
                            'q' => "Як зв'язатися з {$master->name}?",
                            'a' => "Відкрийте {$master->name} на карті {$this->brandName()}, щоб зателефонувати напряму, відкрити навігацію та переглянути перелік послуг перед візитом.",
                        ],
                        [
                            'q' => "Які послуги надає {$master->name}?",
                            'a' => $services !== []
                                ? "{$master->name} пропонує послуги: " . implode(', ', $services) . '. Відкрийте профіль на карті, щоб побачити повний список.'
                                : "Відкрийте профіль на карті, щоб переглянути актуальний перелік послуг {$master->name}.",
                        ],
                    ],
                ];

                return $this->formatEntry(
                    "master:{$master->slug}",
                    'master',
                    $master->name,
                    $this->profilePath((string) $master->slug),
                    $default,
                );
            });
    }

    private function cityEntries(string $search): Collection
    {
        return City::query()
            ->whereHas('masters')
            ->withCount('masters')
            ->with('masters.services.translations')
            ->orderBy('name')
            ->get()
            ->filter(fn (City $city) => $search === '' || Str::contains(Str::lower($city->name), $search))
            ->map(function (City $city) {
                $popularServiceNames = $city->masters
                    ->flatMap(fn (Master $master) => $master->services)
                    ->unique('id')
                    ->take(4)
                    ->map(fn (Service $service) => $service->translate(app()->getLocale()))
                    ->values()
                    ->all();

                $copy = $this->copyGenerator->citySeo($city, (int) $city->masters_count, $popularServiceNames, $this->brand(), $this->brandName());

                $default = [
                    'title' => $copy['metaTitle'],
                    'description' => $copy['description'],
                    'intro' => $copy['intro'],
                    'sections' => $copy['sections'],
                    'faq' => $copy['faq'],
                ];

                return $this->formatEntry(
                    'city:' . Str::slug($city->name),
                    'city',
                    $city->name,
                    '/city/' . Str::slug($city->name),
                    $default,
                );
            });
    }

    private function cityServiceEntries(string $search): Collection
    {
        $masters = Master::with(['city', 'services.translations'])
            ->whereNotNull('city_id')
            ->orderBy('name')
            ->get();

        $combos = [];

        foreach ($masters as $master) {
            $city = $master->city;
            if (!$city) {
                continue;
            }

            foreach ($master->services as $service) {
                $key = 'city_service:' . Str::slug($city->name) . ':' . Str::slug($service->name);
                $combos[$key] ??= ['city' => $city, 'service' => $service, 'count' => 0];
                $combos[$key]['count']++;
            }
        }

        return collect($combos)
            ->filter(function (array $combo) use ($search) {
                if ($search === '') {
                    return true;
                }

                $label = $combo['service']->translate(app()->getLocale()) . ' · ' . $combo['city']->name;

                return Str::contains(Str::lower($label), $search);
            })
            ->map(function (array $combo, string $key) {
                $city = $combo['city'];
                $service = $combo['service'];
                $serviceName = $service->translate(app()->getLocale());
                $copy = $this->copyGenerator->cityServiceSeo($city, $service, $serviceName, $combo['count'], $this->brand(), $this->brandName());

                $default = [
                    'title' => $copy['metaTitle'],
                    'description' => $copy['description'],
                    'intro' => $copy['intro'],
                    'sections' => $copy['sections'],
                    'faq' => $copy['faq'],
                ];

                return $this->formatEntry(
                    $key,
                    'city_service',
                    "{$serviceName} · {$city->name}",
                    '/city/' . Str::slug($city->name) . '/' . Str::slug($service->name),
                    $default,
                );
            })
            ->values();
    }

    private function serviceEntries(string $search): Collection
    {
        return Service::query()
            ->whereHas('masters')
            ->withCount('masters')
            ->with('translations')
            ->orderBy('name')
            ->get()
            ->filter(fn (Service $service) => $search === '' || Str::contains(Str::lower($service->translate(app()->getLocale())), $search))
            ->map(function (Service $service) {
                $serviceName = $service->translate(app()->getLocale());
                $copy = $this->copyGenerator->serviceSeo($service, $serviceName, (int) $service->masters_count, $this->brand(), $this->brandName());

                $default = [
                    'title' => $copy['metaTitle'],
                    'description' => $copy['description'],
                    'intro' => $copy['intro'],
                    'sections' => $copy['sections'],
                    'faq' => $copy['faq'],
                ];

                return $this->formatEntry(
                    'service:' . Str::slug($service->name),
                    'service',
                    $serviceName,
                    '/service/' . Str::slug($service->name),
                    $default,
                );
            });
    }

    private function formatEntry(
        string $key,
        string $type,
        string $label,
        string $route,
        array $default
    ): array {
        $override = $this->overridesService->get($key, $this->brand());

        return [
            'key' => $key,
            'type' => $type,
            'label' => $label,
            'route' => $route,
            'default' => $default,
            'override' => $override,
            'final' => array_replace_recursive($default, $override),
        ];
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

    private function profilePath(string $slug): string
    {
        return $this->brand() === AppBrand::FLOXCITY
            ? "/salon/{$slug}"
            : "/sto/{$slug}";
    }
}
