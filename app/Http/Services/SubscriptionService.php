<?php

namespace App\Http\Services;

use App\DTO\SubscriptionStatus;
use App\Models\Master;
use App\Models\AppSetting;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;

class SubscriptionService
{
    public function verifyAndStore(int $userId, string $platform, string $receiptOrToken, ?string $productId = null): SubscriptionStatus
    {
        $result = $this->verifyRemote($platform, $receiptOrToken, $productId);

        $expiresAt = $result->expires_at ? Carbon::parse($result->expires_at) : null;

        $subscription = Subscription::updateOrCreate(
            [
                'user_id' => $userId,
                'platform' => $platform,
                'external_id' => $result->external_id,
            ],
            [
                'product_id' => $productId ?? $result->product_id,
                'status' => $result->active ? 'active' : 'expired',
                'expires_at' => $expiresAt,
                'last_verified_at' => now(),
                'raw_payload' => $result->raw_payload,
            ]
        );

        // Sync premium flags to master's record(s) for this user
        try {
            Master::where('user_id', $userId)->update([
                'is_premium' => (bool) $result->active,
                'premium_until' => $expiresAt,
            ]);
        } catch (\Throwable $_) {
            // Soft-fail; do not block subscription flow
        }

        return new SubscriptionStatus(
            active: (bool) $result->active,
            platform: $platform,
            product_id: $subscription->product_id,
            expires_at: $subscription->expires_at
        );
    }

    public function getStatus(int $userId): SubscriptionStatus
    {
        $sub = Subscription::where('user_id', $userId)
            ->orderByDesc('expires_at')
            ->first();

        $active = $sub && ($sub->status === 'active') && ($sub->expires_at === null || $sub->expires_at->isFuture());

        return new SubscriptionStatus(
            active: (bool) $active,
            platform: $sub?->platform,
            product_id: $sub?->product_id,
            expires_at: $sub?->expires_at
        );
    }

    public function startTrial(int $userId): SubscriptionStatus
    {
        // Admin configurable subscription config
        $admin = \App\Models\AppSetting::where('key', 'subscription_config')->value('value') ?? [];
        $trialEnabled = array_key_exists('trial_enabled', $admin) ? (bool) $admin['trial_enabled'] : (bool) config('subscription.trial_enabled');
        $trialDays = array_key_exists('trial_days', $admin) ? (int) $admin['trial_days'] : (int) config('subscription.trial_days', 30);

        if (! $trialEnabled) {
            abort(403, 'Trial disabled');
        }
        // If user has any active subscription (including trial), deny
        $current = $this->getStatus($userId);
        if ($current->active) {
            abort(409, 'Subscription already active');
        }
        // Prevent multiple trials: if user had internal trial before, deny
        $hadTrial = Subscription::where('user_id', $userId)
            ->where('platform', 'internal')
            ->where('product_id', 'trial')
            ->exists();
        if ($hadTrial) {
            abort(409, 'Trial already used');
        }

        $expiresAt = now()->addDays($trialDays);
        $trial = Subscription::create([
            'user_id' => $userId,
            'platform' => 'internal',
            'product_id' => 'trial',
            'external_id' => 'trial_'.uniqid(),
            'status' => 'active',
            'expires_at' => $expiresAt,
            'last_verified_at' => now(),
            'raw_payload' => ['type' => 'free_trial'],
        ]);

        // Sync premium flags to master's record(s) for this user
        try {
            \App\Models\Master::where('user_id', $userId)->update([
                'is_premium' => true,
                'premium_until' => $expiresAt,
            ]);
        } catch (\Throwable $_) {
        }

        return new SubscriptionStatus(
            active: true,
            platform: $trial->platform,
            product_id: $trial->product_id,
            expires_at: $trial->expires_at
        );
    }
    public function assertUserHasActiveSubscription(int $userId): void
    {
        $status = $this->getStatus($userId);
        if (! $status->active) {
            abort(402, 'Subscription required');
        }
    }

    /**
     * Verify IAP with Apple or Google. Uses credentials configured in admin settings (app_settings.payments).
     * Replace placeholders with real API calls and auth when ready for production.
     */
    private function verifyRemote(string $platform, string $token, ?string $productId): object
    {
        $settings = optional(AppSetting::where('key', 'payments')->first())->value ?? [];
        $apple = $settings['apple'] ?? [];
        $google = $settings['google'] ?? [];

        if ($platform === 'apple') {
            // Example of where you'd use: $apple['issuer_id'], $apple['key_id'], $apple['private_key'], $apple['bundle_id'], $apple['use_sandbox']
            return (object) [
                'external_id' => substr($token, 0, 64),
                'product_id' => $productId,
                'active' => true,
                'expires_at' => now()->addMonth()->toIso8601String(),
                'raw_payload' => ['platform' => 'apple', 'env' => ($apple['use_sandbox'] ?? true) ? 'sandbox' : 'prod'],
            ];
        }

        if ($platform === 'google') {
            // Example of where you'd use: $google['service_account_json'], $google['package_name']
            return (object) [
                'external_id' => substr($token, 0, 64),
                'product_id' => $productId,
                'active' => true,
                'expires_at' => now()->addMonth()->toIso8601String(),
                'raw_payload' => ['platform' => 'google', 'package' => $google['package_name'] ?? null],
            ];
        }

        abort(422, 'Unknown platform');
    }
}
