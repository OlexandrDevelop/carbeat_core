<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Http\Services\Seo\SeoOverrideBackfillService;
use Illuminate\Console\Command;

/**
 * Run after data changes elsewhere make previously auto-generated SEO copy stale —
 * e.g. merging two services in the admin changes a service's master count, or new
 * cities/services appear. Regenerates only the entries this pipeline itself wrote
 * (tagged `auto_generated`); never touches an entry an admin edited by hand in
 * `/admin/seo-content`. Also refreshes the sitemap, since master/service/city changes
 * that warrant a SEO refresh usually mean the sitemap is stale too.
 */
class RefreshSeoContent extends Command
{
    protected $signature = 'seo:refresh';

    protected $description = 'Regenerate auto-generated city/service SEO copy from current data and refresh the sitemap';

    public function handle(SeoOverrideBackfillService $backfill): int
    {
        $summary = $backfill->syncAll(overwriteAuto: true);

        $this->info(
            "SEO overrides refreshed: {$summary['cities']} cities, {$summary['services']} services updated, " .
            "{$summary['skipped']} manually-edited entries left untouched."
        );

        $this->call('sitemap:generate');

        return self::SUCCESS;
    }
}
