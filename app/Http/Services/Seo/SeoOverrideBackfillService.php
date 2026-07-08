<?php

declare(strict_types=1);

namespace App\Http\Services\Seo;

use App\Enums\AppBrand;
use App\Models\City;
use App\Models\Master;
use App\Models\Service;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Laravel\Telescope\Telescope;

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
 *
 * All entries for a brand live in a single `AppSetting` row (one JSON blob keyed
 * `seo_content_overrides_{brand}`), so every city/service is collected into an
 * in-memory array and written via ONE `SeoOverridesService::putMany()` call per
 * brand at the end, instead of one read-modify-write round trip per entry — with
 * dozens of cities/services that would mean dozens of increasingly large UPDATEs
 * against the very same row. `Telescope::withoutRecording()` additionally keeps
 * this bulk write out of Telescope's query log, since Telescope's own binding
 * formatter (`QueryWatcher::replaceBindings()`) is not built to safely handle a
 * bound value this large.
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

        Telescope::withoutRecording(function () use ($overwriteAuto, &$summary) {
            foreach (AppBrand::cases() as $brand) {
                Config::set('app.client', $brand);
                $brandName = $brand === AppBrand::FLOXCITY ? 'Floxcity' : 'Carbeat';
                $existing = $this->overrides->getAll($brand);

                $entries = [];
                $this->collectCities($brand, $brandName, $overwriteAuto, $existing, $summary, $entries);
                $this->collectServices($brand, $brandName, $overwriteAuto, $existing, $summary, $entries);

                $this->overrides->putMany($entries, $brand);
            }
        });

        return $summary;
    }

    private function collectCities(
        AppBrand $brand,
        string $brandName,
        bool $overwriteAuto,
        array $existing,
        array &$summary,
        array &$entries
    ): void {
        City::query()
            ->whereHas('masters')
            ->withCount('masters')
            ->with('masters.services.translations')
            ->get()
            ->each(function (City $city) use ($brand, $brandName, $overwriteAuto, $existing, &$summary, &$entries) {
                $key = 'city:' . Str::slug($city->name);

                if (!$this->shouldWrite($key, $existing, $overwriteAuto)) {
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
                $entries[$key] = $this->payload($copy);
                $summary['cities']++;
            });
    }

    private function collectServices(
        AppBrand $brand,
        string $brandName,
        bool $overwriteAuto,
        array $existing,
        array &$summary,
        array &$entries
    ): void {
        Service::query()
            ->whereHas('masters')
            ->withCount('masters')
            ->get()
            ->each(function (Service $service) use ($brand, $brandName, $overwriteAuto, $existing, &$summary, &$entries) {
                $key = 'service:' . Str::slug($service->name);

                if (!$this->shouldWrite($key, $existing, $overwriteAuto)) {
                    $summary['skipped']++;

                    return;
                }

                $serviceName = $service->translate('uk');
                $copy = $this->generator->serviceSeo($service, $serviceName, (int) $service->masters_count, $brand, $brandName);
                $entries[$key] = $this->payload($copy);
                $summary['services']++;
            });
    }

    private function shouldWrite(string $key, array $existing, bool $overwriteAuto): bool
    {
        $entry = is_array($existing[$key] ?? null) ? $existing[$key] : [];

        if ($entry === []) {
            return true;
        }

        if (!$overwriteAuto) {
            return false;
        }

        return ($entry['auto_generated'] ?? false) === true;
    }

    private function payload(array $copy): array
    {
        return [
            'title' => $copy['metaTitle'],
            'description' => $copy['description'],
            'intro' => $copy['intro'],
            'sections' => $copy['sections'],
            'faq' => $copy['faq'],
            'auto_generated' => true,
        ];
    }
}
