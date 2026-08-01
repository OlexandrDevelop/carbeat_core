<?php

namespace App\Console\Commands;

use App\Jobs\SyncEasyweekConnectionJob;
use App\Models\EasyweekConnection;
use Illuminate\Console\Command;

class SyncEasyweekConnections extends Command
{
    protected $signature = 'easyweek:sync-connections';

    protected $description = 'Dispatch a config-refresh job for every EasyWeek connection (all brands): validates the slug and caches the primary branch/product id + timezone';

    public function handle(): int
    {
        $connectionIds = EasyweekConnection::withoutGlobalScope('app')
            ->whereIn('sync_status', [EasyweekConnection::STATUS_PENDING, EasyweekConnection::STATUS_ACTIVE, EasyweekConnection::STATUS_ERROR])
            ->pluck('id');

        foreach ($connectionIds as $connectionId) {
            SyncEasyweekConnectionJob::dispatch($connectionId);
        }

        $this->line("Dispatched config sync for {$connectionIds->count()} EasyWeek connection(s).");

        return self::SUCCESS;
    }
}
