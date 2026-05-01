<?php

declare(strict_types=1);

namespace App\Http\Services\Admin;

use App\Enums\AppBrand;
use App\Http\Services\Seo\SeoOverridesService;
use App\Models\City;
use App\Models\Master;
use App\Models\Service;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class SeoContentAdminService
{
    public function __construct(
        private readonly SeoOverridesService $overridesService,
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
                        ($cityName ? " in {$cityName}" : '') .
                        '. View services, location and reviews on the map.',
                        160,
                    ),
                    'intro' => Str::limit((string) ($master->description ?? ''), 220),
                    'sections' => array_values(array_filter([
                        !empty($master->description) ? [
                            'heading' => "About {$master->name}",
                            'body' => Str::limit((string) $master->description, 320),
                        ] : null,
                        ($master->address || $cityName) ? [
                            'heading' => 'Location',
                            'body' => trim(implode(', ', array_filter([$master->address, $cityName]))) . '. Open the marker on the map to build a route and compare nearby stations.',
                        ] : null,
                    ])),
                    'faq' => [
                        [
                            'q' => "How to contact {$master->name}?",
                            'a' => "Open {$master->name} on the {$this->brandName()} map to call directly, open navigation, and review the listed services before visiting.",
                        ],
                        [
                            'q' => "What services does {$master->name} provide?",
                            'a' => $services !== []
                                ? "{$master->name} lists services such as " . implode(', ', $services) . '. Open the profile on the map for the full list.'
                                : "Open the profile on the map to review the services currently listed for {$master->name}.",
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
            ->orderBy('name')
            ->get()
            ->filter(fn (City $city) => $search === '' || Str::contains(Str::lower($city->name), $search))
            ->map(function (City $city) {
                $default = [
                    'title' => "{$city->name} Car Service Map · " . $this->brandName(),
                    'description' => Str::limit(
                        "Find car service stations and auto repair specialists in {$city->name} on {$this->brandName()}. Browse {$city->masters_count} listed stations, services, ratings and direct profile links.",
                        160,
                    ),
                    'intro' => "Browse nearby stations in {$city->name}, compare services, ratings and direct profile pages, and open each station on the map.",
                    'sections' => [
                        [
                            'heading' => "Compare stations in {$city->name}",
                            'body' => "This city page groups stations in {$city->name} into one searchable map experience. You can compare profile pages, ratings, service categories and exact map positions without leaving the page.",
                        ],
                    ],
                    'faq' => [
                        [
                            'q' => "How to find a car service station in {$city->name}?",
                            'a' => "Use the {$this->brandName()} city map for {$city->name} to compare nearby stations, addresses, services and ratings before opening a profile page.",
                        ],
                        [
                            'q' => "Can I browse specific repair categories in {$city->name}?",
                            'a' => 'Yes. Open one of the linked service pages for the city to narrow the map to a specific repair or maintenance category.',
                        ],
                    ],
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
                $citySlug = Str::slug($city->name);
                $serviceSlug = Str::slug($service->name);
                $label = $service->translate(app()->getLocale()) . ' · ' . $city->name;
                $key = "city_service:{$citySlug}:{$serviceSlug}";

                if ($search !== '' && !Str::contains(Str::lower($label), $search)) {
                    continue;
                }

                $combos[$key] = [
                    'key' => $key,
                    'type' => 'city_service',
                    'label' => $label,
                    'route' => "/city/{$citySlug}/{$serviceSlug}",
                    'default' => [
                        'title' => $service->translate(app()->getLocale()) . " in {$city->name} · " . $this->brandName(),
                        'description' => Str::limit(
                            "Find {$service->translate(app()->getLocale())} providers in {$city->name} on {$this->brandName()}. Compare listed stations, addresses, ratings and direct profile pages.",
                            160,
                        ),
                        'intro' => "Browse {$service->translate(app()->getLocale())} providers in {$city->name}, compare station profiles, ratings and addresses, and open each station directly on the map.",
                        'sections' => [
                            [
                                'heading' => "Find {$service->translate(app()->getLocale())} providers in {$city->name}",
                                'body' => "This page focuses the {$this->brandName()} map on {$service->translate(app()->getLocale())} providers in {$city->name}. Use it to compare stations faster than on the full city map.",
                            ],
                        ],
                        'faq' => [
                            [
                                'q' => "How to find {$service->translate(app()->getLocale())} in {$city->name}?",
                                'a' => "Use the {$this->brandName()} service page for {$city->name} to compare {$service->translate(app()->getLocale())} providers, ratings, addresses and direct profile links.",
                            ],
                            [
                                'q' => "Can I switch from {$service->translate(app()->getLocale())} to all stations in {$city->name}?",
                                'a' => "Yes. Open the city landing page to view all stations in {$city->name}, or keep this page to stay focused on {$service->translate(app()->getLocale())}.",
                            ],
                        ],
                    ],
                ];
            }
        }

        return collect($combos)
            ->map(fn (array $entry) => $this->formatEntry(
                $entry['key'],
                $entry['type'],
                $entry['label'],
                $entry['route'],
                $entry['default'],
            ));
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
