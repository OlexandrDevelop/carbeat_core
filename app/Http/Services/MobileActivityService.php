<?php

namespace App\Http\Services;

use Illuminate\Support\Facades\Redis;

class MobileActivityService
{
    /**
     * Get all active users data
     */
    public function getActiveUsers(): array
    {
        $activeIpsKey = "app_activity:active_ips";

        // Get all active IPs from sorted set
        // Laravel Redis returns associative array: ['ip1' => score1, 'ip2' => score2]
        $ips = Redis::zrevrange($activeIpsKey, 0, -1, 'WITHSCORES');

        $users = [];

        // Process IPs from associative array
        if (is_array($ips)) {
            foreach ($ips as $ip => $score) {
                $userData = $this->getUserData($ip);

                if ($userData) {
                    $users[] = $userData;
                }
            }
        }

        return $users;
    }

    /**
     * Get data for specific IP
     */
    public function getUserData(string $ip): ?array
    {
        $key = "app_activity:{$ip}";

        // Check if key exists
        if (!Redis::exists($key)) {
            return null;
        }

        // Get all hash fields
        $data = Redis::hgetall($key);

        if (empty($data)) {
            return null;
        }

        // Get recent actions
        $actions = Redis::lrange("{$key}:actions", 0, 9); // Get last 10 actions
        $actionsDecoded = array_map(fn($action) => json_decode($action, true), $actions);

        return [
            'ip' => $data['ip'] ?? $ip,
            'user_agent' => $data['user_agent'] ?? 'Unknown',
            'dart_version' => $data['dart_version'] ?? null,
            'request_count' => (int)($data['request_count'] ?? 0),
            'last_activity' => (int)($data['last_activity'] ?? 0),
            'last_activity_human' => $this->getTimeAgo((int)($data['last_activity'] ?? 0)),
            'last_endpoint' => $data['last_endpoint'] ?? null,
            'last_method' => $data['last_method'] ?? null,
            'recent_actions' => $actionsDecoded,
        ];
    }

    /**
     * Get statistics
     */
    public function getStats(): array
    {
        $activeIpsKey = "app_activity:active_ips";

        $totalActive = Redis::zcard($activeIpsKey);

        // Get activity in last 5 minutes
        $fiveMinutesAgo = now()->subMinutes(5)->timestamp;
        $activeInLast5Min = Redis::zcount($activeIpsKey, $fiveMinutesAgo, '+inf');

        // Get activity in last 1 minute
        $oneMinuteAgo = now()->subMinutes(1)->timestamp;
        $activeInLast1Min = Redis::zcount($activeIpsKey, $oneMinuteAgo, '+inf');

        // Calculate total requests
        $ips = Redis::zrange($activeIpsKey, 0, -1);
        $totalRequests = 0;

        if (is_array($ips)) {
            foreach ($ips as $ip) {
                $key = "app_activity:{$ip}";
                $count = Redis::hget($key, 'request_count');
                $totalRequests += (int)$count;
            }
        }

        return [
            'total_active_users' => (int)$totalActive,
            'active_last_5_min' => (int)$activeInLast5Min,
            'active_last_1_min' => (int)$activeInLast1Min,
            'total_requests' => $totalRequests,
        ];
    }

    /**
     * Clear all activity data
     */
    public function clearAll(): void
    {
        $activeIpsKey = "app_activity:active_ips";
        $ips = Redis::zrange($activeIpsKey, 0, -1);

        if (is_array($ips)) {
            foreach ($ips as $ip) {
                $key = "app_activity:{$ip}";
                Redis::del($key);
                Redis::del("{$key}:actions");
            }
        }

        Redis::del($activeIpsKey);
    }

    /**
     * Get human-readable time ago
     */
    private function getTimeAgo(int $timestamp): string
    {
        $diff = now()->timestamp - $timestamp;

        if ($diff < 60) {
            return "{$diff}s ago";
        } elseif ($diff < 3600) {
            $minutes = floor($diff / 60);
            return "{$minutes}m ago";
        } else {
            $hours = floor($diff / 3600);
            return "{$hours}h ago";
        }
    }
}

