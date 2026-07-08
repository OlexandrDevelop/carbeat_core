<?php

declare(strict_types=1);

namespace App\Http\Services\Seo;

use App\Enums\AppBrand;
use App\Models\City;
use App\Models\Master;
use App\Models\Service;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;

/**
 * Writes auto-generated Ukrainian SEO copy (title/description/intro/sections/faq) for
 * every city and service, for both brands. Shared by two callers:
 * - `database/migrations/2026_07_08_120000_seed_ukrainian_seo_overrides.php` (one-time
 *   backfill on a fresh install — `$overwriteAuto = false`, never touches an entry that
 *   already has any content at all, hand-edited or not).
 * - `php artisan seo:refresh` (run after data changes elsewhere make previously
 *   generated text stale — e.g. merging two services in the admin changes a service's
 *   master count) — `$overwriteAuto = true`, refreshes only entries this pipeline
 *   itself wrote (tagged `auto_generated`), never touches one an admin typed by hand
 *   in `/admin/seo-content`.
 */
class SeoOverrideBackfillService
{
    public function __construct(
        private readonly SeoOverridesService $overrides,
        private readonly UkrainianSeoCopyGenerator $generator,
    ) {
    }

    /**
     * @return array{cities: int, services: int, skipped: int}
     */
    public function syncAll(bool $overwriteAuto): array
    {
        $summary = ['cities' => 0, 'services' => 0, 'skipped' => 0];

        foreach (AppBrand::cases() as $brand) {
            Config::set('app.client', $brand);
            $brandName = $brand === AppBrand::FLOXCITY ? 'Floxcity' : 'Carbeat';

            $this->syncCities($brand, $brandName, $overwriteAuto, $summary);
            $this->syncServices($brand, $brandName, $overwriteAuto, $summary);
        }

        return $summary;
    }

    private function syncCities(AppBrand $brand, string $brandName, bool $overwriteAuto, array &$summary): void
    {
        City::query()
            ->whereHas('masters')
            ->withCount('masters')
            ->with('masters.services.translations')
            ->get()
            ->each(function (City $city) use ($brand, $brandName, $overwriteAuto, &$summary) {
                $key = 'city:' . Str::slug($city->name);

                if (!$this->shouldWrite($key, $brand, $overwriteAuto)) {
                    $summary['skipped']++;

                    return;
                }

                $popularServiceNames = $city->masters
                    ->flatMap(fn (Master $master) => $master->services)
                    ->unique('id')
                    ->take(4)
                    ->map(fn (Service $service) => $service->translate('uk'))
                    ->values()
                    ->all();

                $copy = $this->generator->citySeo($city, (int) $city->masters_count, $popularServiceNames, $brand, $brandName);
                $this->write($key, $copy, $brand);
                $summary['cities']++;
            });
    }

    private function syncServices(AppBrand $brand, string $brandName, bool $overwriteAuto, array &$summary): void
    {
        Service::query()
            ->whereHas('masters')
            ->withCount('masters')
            ->get()
            ->each(function (Service $service) use ($brand, $brandName, $overwriteAuto, &$summary) {
                $key = 'service:' . Str::slug($service->name);

                if (!$this->shouldWrite($key, $brand, $overwriteAuto)) {
                    $summary['skipped']++;

                    return;
                }

                $serviceName = $service->translate('uk');
                $copy = $this->generator->serviceSeo($service, $serviceName, (int) $service->masters_count, $brand, $brandName);
                $this->write($key, $copy, $brand);
                $summary['services']++;
            });
    }

    private function shouldWrite(string $key, AppBrand $brand, bool $overwriteAuto): bool
    {
        $existing = $this->overrides->get($key, $brand);

        if ($existing === []) {
            return true;
        }

        if (!$overwriteAuto) {
            return false;
        }

        return ($existing['auto_generated'] ?? false) === true;
    }

    private function write(string $key, array $copy, AppBrand $brand): void
    {
        $this->overrides->put($key, [
            'title' => $copy['metaTitle'],
            'description' => $copy['description'],
            'intro' => $copy['intro'],
            'sections' => $copy['sections'],
            'faq' => $copy['faq'],
            'auto_generated' => true,
        ], $brand);
    }
}
