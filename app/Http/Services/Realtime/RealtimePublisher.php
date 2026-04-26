<?php

namespace App\Http\Services\Realtime;

use App\Models\Master;
use Illuminate\Support\Facades\Redis;
use Throwable;

class RealtimePublisher
{
    /**
     * Publish a master-created event to Redis with flavor-aware channel.
     * Keeps the try/catch and logging local to avoid polluting controllers.
     */
    public function publishMasterCreated(Master $master, array $payload): void
    {
        // Determine flavor: prefer master.app, fallback to runtime config set by middleware
        $flavor = 'carbeat';

        if (!empty($master->app)) {
            $flavor = $master->app;
        }

        $event = array_merge(['type' => 'master:created'], $payload, ['flavor' => $flavor]);
        $channel = $flavor . ':masters:events';

        try {
            Redis::publish($channel, json_encode($event));
        } catch (Throwable $e) {
            // Log but do not break flow
            logger()->error('Failed to publish master created event to Redis: ' . $e->getMessage());
        }
    }

    public function publishStatusRequestUpdate(array $payload): void
    {
        $flavor = $payload['flavor'] ?? 'carbeat';
        $channel = $flavor . ':status-requests:events';

        try {
            Redis::publish($channel, json_encode($payload));
        } catch (Throwable $e) {
            logger()->error('Failed to publish status request event to Redis: ' . $e->getMessage());
        }
    }
}
