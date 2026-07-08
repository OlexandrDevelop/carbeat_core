<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Http\Services\Seo\SeoOverrideBackfillService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Artisan;

/**
 * Regenerates auto-generated city/service SEO copy (and the sitemap) after data that
 * feeds it changes — masters/services/cities created, edited or deleted. Dispatched
 * from model observers (see `AppServiceProvider::boot()`) and, at the few call sites
 * that mutate `masters`/`master_services`/`services` via raw query-builder calls
 * bypassing Eloquent events entirely (service merge/delete/provider reassignment in
 * `ServiceAdminService`), explicitly from those methods.
 *
 * Never touches an entry an admin edited by hand in `/admin/seo-content` — see
 * `SeoOverrideBackfillService::shouldWrite()`.
 *
 * `ShouldBeUnique` + the delay in `queue()` collapse bursts (e.g. a bulk import
 * touching hundreds of masters) into a single run: once one instance is queued,
 * further dispatches within `$uniqueFor` seconds are silently dropped instead of
 * piling up duplicate work.
 */
class RefreshSeoOverridesJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $uniqueFor = 300;

    public function uniqueId(): string
    {
        return 'seo-overrides-refresh';
    }

    /**
     * Preferred way to enqueue this job — the short delay lets a burst of model
     * events (e.g. an import loop) settle before the single deduplicated run fires.
     */
    public static function queue(): void
    {
        self::dispatch()->delay(now()->addSeconds(60));
    }

    public function handle(SeoOverrideBackfillService $backfill): void
    {
        $backfill->syncAll(overwriteAuto: true);

        Artisan::call('sitemap:generate');
    }
}
