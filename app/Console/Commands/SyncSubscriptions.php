<?php

namespace App\Console\Commands;

use App\Models\Master;
use App\Models\Subscription;
use Illuminate\Console\Command;

class SyncSubscriptions extends Command
{
    protected $signature = 'subscriptions:sync {--chunk=200 : Number of subscriptions to process per chunk}';

    protected $description = 'Sync master premium flags based on subscription expiration dates';

    public function handle(): int
    {
        $chunkSize = (int) $this->option('chunk');
        $chunkSize = $chunkSize > 0 ? $chunkSize : 200;

        $processedSubscriptions = 0;
        $updatedMasters = 0;

        $now = now();

        Subscription::orderBy('id')
            ->chunk($chunkSize, function ($subscriptions) use (&$processedSubscriptions, &$updatedMasters, $now) {
                foreach ($subscriptions as $subscription) {
                    $isActive = $subscription->expires_at === null || $subscription->expires_at->isFuture();
                    $newStatus = $isActive ? 'active' : 'expired';

                    if ($subscription->status !== $newStatus || $subscription->last_verified_at === null || $subscription->last_verified_at->lt($now)) {
                        $subscription->status = $newStatus;
                        $subscription->last_verified_at = $now;
                        $subscription->save();
                    } else {
                        $subscription->touch('last_verified_at');
                    }

                    $updatedMasters += Master::where('user_id', $subscription->user_id)->update([
                        'is_premium' => $isActive,
                        'premium_until' => $subscription->expires_at,
                    ]);

                    $processedSubscriptions++;
                }
            });

        $expiredMasters = Master::whereNotNull('premium_until')
            ->where('premium_until', '<=', $now)
            ->update([
                'is_premium' => false,
            ]);

        $this->info(sprintf(
            'Processed %d subscriptions. Updated %d masters. Reset %d expired masters.',
            $processedSubscriptions,
            $updatedMasters,
            $expiredMasters
        ));

        return Command::SUCCESS;
    }
}

