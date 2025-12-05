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

        $isActive = (bool) $result->active;
        if ($expiresAt instanceof Carbon) {
            $isActive = $expiresAt->isFuture();
        }

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
                'is_premium' => $isActive,
                'premium_until' => $expiresAt,
            ]);
        } catch (\Throwable $_) {
            // Soft-fail; do not block subscription flow
        }

        return new SubscriptionStatus(
            active: $isActive,
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
