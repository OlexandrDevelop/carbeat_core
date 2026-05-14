<?php

namespace App\Console\Commands;

use App\Enums\AppBrand;
use App\Models\Master;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;

class GenerateCleanSitemap extends Command
{
    private const MAX_PROFILE_URLS = 40;
    private const MIN_DESCRIPTION_LENGTH = 160;

    protected $signature = 'sitemap:generate-clean';
    protected $description = 'Generate a small clean sitemap for Google with only high-quality Carbeat STO pages.';

    public function handle(): int
    {
        Config::set('app.client', AppBrand::CARBEAT);
        Config::set('app.url', $this->baseUrl());

        $xml = $this->buildXml();
        $targetPath = storage_path('app/public/sitemap-clean.xml');

        $directory = dirname($targetPath);
        if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
            $this->error('Failed to create sitemap directory.');

            return self::FAILURE;
        }

        if (file_put_contents($targetPath, $xml) === false) {
            $this->error('Failed to write clean sitemap file.');

            return self::FAILURE;
        }

        $this->info('Clean sitemap generated successfully.');

        return self::SUCCESS;
    }

    private function buildXml(): string
    {
        $writer = new \XMLWriter();
        $writer->openMemory();
        $writer->startDocument('1.0', 'UTF-8');
        $writer->setIndent(true);
        $writer->startElement('urlset');
        $writer->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

        $this->writeUrl($writer, $this->baseUrl() . '/', null);

        $masters = Master::query()
            ->select(['slug', 'updated_at'])
            ->whereNotNull('slug')
            ->where('slug', '!=', '')
            ->whereNotNull('description')
            ->whereRaw('CHAR_LENGTH(TRIM(description)) >= ?', [self::MIN_DESCRIPTION_LENGTH])
            ->whereNotNull('photo')
            ->where('photo', '!=', '')
            ->whereNotNull('contact_phone')
            ->where('contact_phone', '!=', '')
            ->whereNotNull('address')
            ->where('address', '!=', '')
            ->where(function ($query) {
                $query
                    ->where('rating', '>', 0)
                    ->orWhere('rating_google', '>', 0)
                    ->orWhere('reviews_count', '>', 0);
            })
            ->orderByDesc('reviews_count')
            ->orderByDesc('rating')
            ->orderByDesc('rating_google')
            ->orderByDesc('updated_at')
            ->limit(self::MAX_PROFILE_URLS)
            ->get();

        foreach ($masters as $master) {
            $this->writeUrl(
                $writer,
                $this->baseUrl() . '/sto/' . $master->slug,
                $master->updated_at?->toAtomString(),
            );
        }

        $writer->endElement();
        $writer->endDocument();

        return $writer->outputMemory();
    }

    private function writeUrl(\XMLWriter $writer, string $loc, ?string $lastmod): void
    {
        $writer->startElement('url');
        $writer->writeElement('loc', $loc);

        if ($lastmod !== null) {
            $writer->writeElement('lastmod', $lastmod);
        }

        $writer->endElement();
    }

    private function baseUrl(): string
    {
        $urls = (array) config('app.brand_urls', []);

        return rtrim((string) ($urls[AppBrand::CARBEAT->value] ?? 'https://carbeat.online'), '/');
    }
}
