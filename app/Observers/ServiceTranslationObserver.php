<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\RefreshSeoOverridesJob;
use App\Models\ServiceTranslation;

/**
 * `Service::translate('uk')` (the name shown everywhere in generated SEO copy) reads
 * from this table, not `services.name` — so editing a translation in the admin
 * (`ServiceAdminService::update()`) needs the same refresh as editing the service
 * itself.
 */
class ServiceTranslationObserver
{
    public function saved(ServiceTranslation $translation): void
    {
        if ($translation->locale === 'uk') {
            RefreshSeoOverridesJob::queue();
        }
    }

    public function deleted(ServiceTranslation $translation): void
    {
        if ($translation->locale === 'uk') {
            RefreshSeoOverridesJob::queue();
        }
    }
}
