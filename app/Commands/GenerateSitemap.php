<?php

namespace App\Commands;

use App\Models\Master;
use Illuminate\Console\Command;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\Tags\Url;

class GenerateSitemap extends Command
{
    protected $signature = 'sitemap:generate';
    protected $description = 'Generate the sitemap.';

    public function handle(): int
    {
        $this->info('Generating sitemap...');

        $sitemap = Sitemap::create()
            ->add(Url::create('/')
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_WEEKLY)
                ->setPriority(1.0))
            ->add(Url::create('/masters')
                ->setChangeFrequency(Url::CHANGE_FREQUENCY_DAILY)
                ->setPriority(0.9));

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

        // Write to storage/app/public (writable directory)
        $sitemapPath = storage_path('app/public/sitemap.xml');
        $sitemap->writeToFile($sitemapPath);

        // Create symlink in public directory
        $publicSitemapPath = public_path('sitemap.xml');
        if (file_exists($publicSitemapPath)) {
            if (is_link($publicSitemapPath)) {
                unlink($publicSitemapPath);
            } else {
                // If it's a regular file, remove it to create symlink
                unlink($publicSitemapPath);
            }
        }
        // Create symlink to the sitemap in storage
        symlink($sitemapPath, $publicSitemapPath);

        $this->info('Sitemap generated successfully.');
        return self::SUCCESS;
    }
}
