<?php

namespace App\Jobs;

use App\Http\Services\EasyWeek\EasyWeekConnectionService;
use App\Models\EasyweekConnection;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class SyncEasyweekConnectionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $connectionId) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping("easyweek-connection-{$this->connectionId}"))->releaseAfter(60)];
    }

    public function handle(EasyWeekConnectionService $service): void
    {
        $connection = EasyweekConnection::withoutGlobalScope('app')->find($this->connectionId);
        if (! $connection) {
            return;
        }

        $service->syncConfig($connection);
    }
}
