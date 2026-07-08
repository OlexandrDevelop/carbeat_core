<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\RefreshSeoOverridesJob;
use App\Models\Service;

/**
 * Note: the admin "merge services" and "delete service" flows (`ServiceAdminService`)
 * mutate `services`/`master_services` via raw query-builder calls and mass Eloquent
 * deletes, neither of which fire these events — those call sites dispatch
 * `RefreshSeoOverridesJob` explicitly instead. This observer only catches Service
 * records changed through normal single-model saves/deletes elsewhere.
 */
class ServiceObserver
{
    public function updated(Service $service): void
    {
        if ($service->wasChanged('name')) {
            RefreshSeoOverridesJob::queue();
        }
    }

    public function deleted(Service $service): void
    {
        RefreshSeoOverridesJob::queue();
    }
}
