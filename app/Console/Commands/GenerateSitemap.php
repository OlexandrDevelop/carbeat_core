<?php

namespace App\Console\Commands;

use App\Enums\AppBrand;
use App\Models\City;
use App\Models\Master;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate the sitemap.';

    public function handle()
    {
        foreach (AppBrand::cases() as $brand) {
            $this->generateForBrand($brand);
        }

        $this->info('Sitemaps generated successfully.');
    }

    private function generateForBrand(AppBrand $brand): void
    {
        Config::set('app.client', $brand);
        $baseUrl = $this->baseUrl($brand);

        $this->info("Generating sitemap for {$brand->value}...");

        $sitemap = Sitemap::create()
            ->add(Url::create($baseUrl)
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(1.0));

        Master::select(['slug', 'updated_at'])->chunk(100, function ($masters) use ($sitemap) {
            foreach ($masters as $master) {
                $sitemap->add(
                    Url::create($this->absoluteUrl("/sto/{$master->slug}"))
                        ->setLastModificationDate($master->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                        ->setPriority(0.8)
                );
            }
        });

        City::query()->whereHas('masters')->get(['id', 'name', 'updated_at'])->each(function ($city) use ($sitemap) {
            $citySlug = Str::slug($city->name);
            $sitemap->add(
                Url::create($this->absoluteUrl("/city/{$citySlug}"))
                    ->setLastModificationDate($city->updated_at)
                    ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                    ->setPriority(0.75)
            );

            Service::query()
                ->whereHas('masters', fn ($masters) => $masters->where('city_id', $city->id))
                ->get(['id', 'name', 'updated_at'])
                ->each(function ($service) use ($sitemap, $citySlug, $city) {
                    $lastModified = $service->updated_at && $city->updated_at
                        ? $service->updated_at->greaterThan($city->updated_at)
                            ? $service->updated_at
                            : $city->updated_at
                        : ($service->updated_at ?? $city->updated_at);

                    $sitemap->add(
                        Url::create($this->absoluteUrl("/city/{$citySlug}/" . Str::slug($service->name)))
                            ->setLastModificationDate($lastModified)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                            ->setPriority(0.7)
                    );
                });
        });

        $sitemap->writeToFile(storage_path("app/public/sitemap-{$brand->value}.xml"));
    }

    private function absoluteUrl(string $path): string
    {
        return rtrim((string) config('app.url'), '/') . $path;
    }

    private function baseUrl(AppBrand $brand): string
    {
        $urls = (array) config('app.brand_urls', []);
        $fallback = $brand === AppBrand::FLOXCITY ? 'https://flox.city' : 'https://carbeat.online';
        $url = (string) ($urls[$brand->value] ?? $fallback);
        Config::set('app.url', $url);

        return rtrim($url, '/');
    }
}
