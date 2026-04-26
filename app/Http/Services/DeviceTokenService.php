<?php

namespace App\Http\Services;

use App\Enums\AppBrand;
use App\Models\DeviceToken;
use App\Models\User;

class DeviceTokenService
{
    public function register(User $user, array $payload): DeviceToken
    {
        $app = $user->app;
        if ($app instanceof AppBrand) {
            $app = $app->value;
        }

        return DeviceToken::updateOrCreate(
            ['token' => $payload['token']],
            [
                'user_id' => $user->id,
                'platform' => $payload['platform'] ?? null,
                'app' => is_string($app) && $app !== '' ? $app : 'carbeat',
                'active' => true,
                'last_seen_at' => now(),
            ]
        );
    }

    public function deactivate(string $token): void
    {
        DeviceToken::query()
            ->where('token', $token)
            ->update(['active' => false]);
    }
}
