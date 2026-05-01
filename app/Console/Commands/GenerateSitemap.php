<?php

namespace App\Console\Commands;

use App\Models\City;
use App\Models\Master;
use App\Models\Service;
use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate the sitemap.';

    public function handle()
    {
        $this->info('Generating sitemap...');

        $sitemap = Sitemap::create()
            ->add(Url::create('/')
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(1.0));

        // Add all master profiles
        Master::select(['slug', 'updated_at'])->chunk(100, function ($masters) use ($sitemap) {
            foreach ($masters as $master) {
                $sitemap->add(
                    Url::create("/sto/{$master->slug}")
                        ->setLastModificationDate($master->updated_at)
                        ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                        ->setPriority(0.8)
                );
            }
        });

        City::query()->whereHas('masters')->get(['id', 'name', 'updated_at'])->each(function ($city) use ($sitemap) {
            $citySlug = Str::slug($city->name);
            $sitemap->add(
                Url::create("/city/{$citySlug}")
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
                        Url::create("/city/{$citySlug}/" . Str::slug($service->name))
                            ->setLastModificationDate($lastModified)
                            ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                            ->setPriority(0.7)
                    );
                });
        });

        $sitemap->writeToFile(public_path('sitemap.xml'));

        $this->info('Sitemap generated successfully.');
    }
}
