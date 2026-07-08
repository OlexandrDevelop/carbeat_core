<?php

namespace App\Observers;

use App\Enums\AppBrand;
use App\Http\Services\Master\MasterService;
use App\Jobs\RefreshSeoOverridesJob;
use App\Models\Master;

class MasterObserver
{
    /**
     * Handle the Master "created" event.
     */
    public function created(Master $master): void
    {
        RefreshSeoOverridesJob::queue();
    }

    public function creating(Master $master): void
    {
        $master->slug = $this->uniqueSlug(MasterService::generateSlug($master), $master->app);
    }

    /**
     * Two masters (e.g. different branches of the same chain) can generate the same
     * base slug from name+specialty; masters_slug_app_unique then rejects the insert.
     * Append -2, -3, ... until the slug is free within the brand.
     */
    protected function uniqueSlug(string $baseSlug, ?string $app): string
    {
        $brand = $app ?: (config('app.client') instanceof AppBrand
            ? config('app.client')->value
            : (config('app.client') ?: AppBrand::CARBEAT->value));

        $slug = $baseSlug;
        $suffix = 2;

        while (Master::withoutGlobalScope('app')->where('app', $brand)->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    /**
     * Handle the Master "updated" event.
     *
     * Dispatched unconditionally rather than gated on `wasChanged(['city_id', ...])`:
     * a master's `services()` many-to-many sync (which also affects the popular-services
     * text on city pages) doesn't touch those scalar columns at all, so it wouldn't be
     * caught by a narrower check — and the job is debounced/idempotent, so an
     * occasional unnecessary refresh costs nothing.
     */
    public function updated(Master $master): void
    {
        RefreshSeoOverridesJob::queue();
    }

    /**
     * Handle the Master "deleted" event.
     */
    public function deleted(Master $master): void
    {
        RefreshSeoOverridesJob::queue();
    }

    /**
     * Handle the Master "restored" event.
     */
    public function restored(Master $master): void
    {
        //
    }

    /**
     * Handle the Master "force deleted" event.
     */
    public function forceDeleted(Master $master): void
    {
        //
    }
}
