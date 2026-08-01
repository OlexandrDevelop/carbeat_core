<?php

namespace App\Jobs;

use App\Http\Services\EasyWeek\EasyWeekAvailabilityService;
use App\Models\EasyweekConnection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class SyncEasyweekAvailabilityJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $connectionId) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping("easyweek-availability-{$this->connectionId}"))->releaseAfter(60)];
    }

    public function handle(EasyWeekAvailabilityService $service): void
    {
        $connection = EasyweekConnection::withoutGlobalScope('app')->find($this->connectionId);
        if (! $connection) {
            return;
        }

        $service->sync($connection);
    }
}
