<?php

namespace App\Http\Services;

use App\DTO\SubscriptionStatus;
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

    public function assertUserHasActiveSubscription(int $userId): void
    {
        $status = $this->getStatus($userId);
        if (! $status->active) {
            abort(402, 'Subscription required');
        }
    }

    /**
     * Verify IAP with Apple or Google. For production, you must fill env vars and use proper endpoints.
     */
    private function verifyRemote(string $platform, string $token, ?string $productId): object
    {
        if ($platform === 'apple') {
            // Placeholder: in production, call App Store Server API with signed JWT
            // and decode response. Here we trust the token for demo.
            return (object) [
                'external_id' => substr($token, 0, 64),
                'product_id' => $productId,
                'active' => true,
                'expires_at' => now()->addMonth()->toIso8601String(),
                'raw_payload' => ['platform' => 'apple'],
            ];
        }

        if ($platform === 'google') {
            // Placeholder: call Google Play Developer API to validate purchase token
            return (object) [
                'external_id' => substr($token, 0, 64),
                'product_id' => $productId,
                'active' => true,
                'expires_at' => now()->addMonth()->toIso8601String(),
                'raw_payload' => ['platform' => 'google'],
            ];
        }

        abort(422, 'Unknown platform');
    }
}
