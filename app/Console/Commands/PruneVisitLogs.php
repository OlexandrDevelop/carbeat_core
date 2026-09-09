<?php

namespace App\Console\Commands;

use App\Models\VisitSession;
use Illuminate\Console\Command;

class PruneVisitLogs extends Command
{
    protected $signature = 'visits:prune {--days=180}';

    protected $description = 'Delete visit sessions (and their events, via cascade) older than the retention window';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $deleted = VisitSession::query()
            ->withoutGlobalScope('app')
            ->where('last_seen_at', '<', $cutoff)
            ->delete();

        $this->info("Deleted {$deleted} visit session(s) older than {$days} days.");

        return self::SUCCESS;
    }
}
