<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\RefreshSeoOverridesJob;
use App\Models\City;

class CityObserver
{
    public function updated(City $city): void
    {
        if ($city->wasChanged('name')) {
            RefreshSeoOverridesJob::queue();
        }
    }

    public function deleted(City $city): void
    {
        RefreshSeoOverridesJob::queue();
    }
}
