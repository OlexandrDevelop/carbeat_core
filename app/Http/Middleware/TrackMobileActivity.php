<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redis;
use Symfony\Component\HttpFoundation\Response;

class TrackMobileActivity
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Track only mobile app requests (Dart user-agent)
        $userAgent = $request->userAgent();
        if (!$userAgent || !str_contains($userAgent, 'Dart/')) {
            return $response;
        }

        // Get IP address
        $ip = $request->ip();

        // Don't track if no IP
        if (!$ip) {
            return $response;
        }

        try {
            $this->trackActivity($request, $ip, $userAgent);
        } catch (\Exception $e) {
            // Silent fail - don't break the request if tracking fails
            report($e);
        }

        return $response;
    }

    /**
     * Track activity in Redis
     */
    private function trackActivity(Request $request, string $ip, string $userAgent): void
    {
        $now = now()->timestamp;
        $key = "app_activity:{$ip}";
        $activeIpsKey = "app_activity:active_ips";

        // Get current data
        $exists = Redis::exists($key);

        // Increment request counter
        Redis::hincrby($key, 'request_count', 1);

        // Update last activity
        Redis::hset($key, 'last_activity', $now);

        // Store user agent (if not set or changed)
        Redis::hset($key, 'user_agent', $userAgent);

        // Store IP itself (for easier reference)
        Redis::hset($key, 'ip', $ip);

        // Extract app version from user agent if available
        if (preg_match('/Dart\/([0-9.]+)/', $userAgent, $matches)) {
            Redis::hset($key, 'dart_version', $matches[1]);
        }

        // Store endpoint and method
        $endpoint = $request->path();
        $method = $request->method();

        Redis::hset($key, 'last_endpoint', $endpoint);
        Redis::hset($key, 'last_method', $method);

        // Add action to the list (keep last 20 actions)
        $action = json_encode([
            'endpoint' => $endpoint,
            'method' => $method,
            'time' => $now,
            'description' => $this->getActionDescription($endpoint, $method),
        ]);

        Redis::lpush("{$key}:actions", $action);
        Redis::ltrim("{$key}:actions", 0, 19); // Keep only last 20 actions

        // Set TTL to 1 hour
        Redis::expire($key, 3600);
        Redis::expire("{$key}:actions", 3600);

        // Add IP to active IPs sorted set (score = timestamp)
        Redis::zadd($activeIpsKey, $now, $ip);

        // Clean up old IPs from sorted set (older than 1 hour)
        $cutoff = $now - 3600;
        Redis::zremrangebyscore($activeIpsKey, 0, $cutoff);
    }

    /**
     * Get human-readable description of the action
     */
    private function getActionDescription(string $endpoint, string $method): string
    {
        // API v1 endpoints
        if (str_starts_with($endpoint, 'api/v1/')) {
            $endpoint = str_replace('api/v1/', '', $endpoint);

            return match(true) {
                str_starts_with($endpoint, 'masters') && $method === 'GET' && !str_contains($endpoint, '/') => 'Viewing masters list',
                str_starts_with($endpoint, 'masters/') && str_contains($endpoint, '/availability') => 'Checking master availability',
                str_starts_with($endpoint, 'masters/') && str_contains($endpoint, '/slots') => 'Viewing available time slots',
                str_starts_with($endpoint, 'masters/') && $method === 'GET' => 'Viewing master details',
                str_starts_with($endpoint, 'services') => 'Viewing services',
                str_starts_with($endpoint, 'bookings') && $method === 'POST' => 'Creating booking',
                str_starts_with($endpoint, 'bookings') && $method === 'GET' => 'Viewing bookings',
                str_starts_with($endpoint, 'auth/login') => 'Login',
                str_starts_with($endpoint, 'auth/register') => 'Registration',
                str_starts_with($endpoint, 'auth/verify') => 'Phone verification',
                str_starts_with($endpoint, 'account') => 'Account management',
                str_starts_with($endpoint, 'reviews') => 'Reviews',
                str_starts_with($endpoint, 'subscriptions') => 'Subscriptions',
                default => "{$method} {$endpoint}",
            };
        }

        return "{$method} {$endpoint}";
    }
}

