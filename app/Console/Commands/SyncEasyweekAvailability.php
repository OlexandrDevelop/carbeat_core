<?php

namespace App\Console\Commands;

use App\Jobs\SyncEasyweekAvailabilityJob;
use App\Models\EasyweekConnection;
use Illuminate\Console\Command;

class SyncEasyweekAvailability extends Command
{
    protected $signature = 'easyweek:sync-availability';

    protected $description = 'Poll EasyWeek calendars for every active connection (all brands) and refresh each master\'s live available/busy status';

    public function handle(): int
    {
        $connectionIds = EasyweekConnection::withoutGlobalScope('app')
            ->where('sync_status', EasyweekConnection::STATUS_ACTIVE)
            ->pluck('id');

        foreach ($connectionIds as $connectionId) {
            SyncEasyweekAvailabilityJob::dispatch($connectionId);
        }

        $this->line("Dispatched availability poll for {$connectionIds->count()} EasyWeek connection(s).");

        return self::SUCCESS;
    }
}
