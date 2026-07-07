<?php

namespace App\Observers;

use App\Enums\AppBrand;
use App\Http\Services\Master\MasterService;
use App\Models\Master;

class MasterObserver
{
    /**
     * Handle the Master "created" event.
     */
    public function created(Master $master): void
    {
        //
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
     */
    public function updated(Master $master): void
    {
        //
    }

    /**
     * Handle the Master "deleted" event.
     */
    public function deleted(Master $master): void
    {
        //
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
