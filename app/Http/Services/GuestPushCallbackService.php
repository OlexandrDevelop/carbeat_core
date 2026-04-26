<?php

namespace App\Http\Services;

use App\Models\GuestPushCallback;

class GuestPushCallbackService
{
    public function register(string $guestDeviceId, array $payload, string $app): GuestPushCallback
    {
        GuestPushCallback::query()
            ->where('guest_device_id', $guestDeviceId)
            ->where('app', $app)
            ->whereNull('deleted_at')
            ->delete();

        return GuestPushCallback::create([
            'guest_device_id' => $guestDeviceId,
            'token' => $payload['guest_push_token'],
            'platform' => $payload['guest_platform'] ?? null,
            'app' => $app,
            'last_seen_at' => now(),
        ]);
    }

    public function consume(?int $callbackId, string $title, string $body, array $data, string $app, FcmService $fcmService): bool
    {
        if (! $callbackId) {
            return false;
        }

        $callback = GuestPushCallback::query()->find($callbackId);
        if (! $callback || $callback->trashed()) {
            return false;
        }

        $sent = $fcmService->sendToToken($callback->token, $title, $body, $data, $app);
        $callback->delete();

        return $sent;
    }
}
