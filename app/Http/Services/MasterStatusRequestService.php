<?php

namespace App\Http\Services;

use App\Enums\AppBrand;
use App\Http\Services\Master\MasterAvailabilityService;
use App\Http\Services\Realtime\RealtimePublisher;
use App\Jobs\ExpireMasterStatusRequestJob;
use App\Models\Master;
use App\Models\MasterStatusRequest;
use App\Models\User;
use Illuminate\Support\Str;

class MasterStatusRequestService
{
    private const REQUEST_TIMEOUT_MINUTES = 5;
    private const DRIVER_COOLDOWN_MINUTES = 15;
    private const AVAILABLE_FOR_MINUTES = 120;

    public function __construct(
        private readonly FcmService $fcmService,
        private readonly SmsService $smsService,
        private readonly MasterAvailabilityService $availabilityService,
        private readonly RealtimePublisher $realtimePublisher,
        private readonly GuestPushCallbackService $guestPushCallbackService,
        private readonly TelegramService $telegramService,
    ) {}

    public function createRequest(?User $driver, array $payload): array
    {
        $masterId = (int) $payload['master_id'];
        $master = Master::with('user')->findOrFail($masterId);
        $masterApp = (string) ($master->app ?? $master->user?->app ?? 'carbeat');
        $driverApp = (string) ($driver?->app ?? $masterApp);
        $cooldownThreshold = now()->subMinutes(self::DRIVER_COOLDOWN_MINUTES);
        $guestDeviceId = $this->normalizeGuestDeviceId($payload['guest_device_id'] ?? null);

        $existing = $this->findRecentRequest($driver, $master->id, $guestDeviceId, $cooldownThreshold);

        if ($existing) {
            return [
                'status' => 'cooldown',
                'request_id' => $existing->id,
                'cooldown_expires_at' => $existing->created_at->copy()->addMinutes(self::DRIVER_COOLDOWN_MINUTES)->toISOString(),
            ];
        }

        $token = Str::lower(Str::random(10));
        $guestPushCallbackId = null;

        if (! $driver && $guestDeviceId && ! empty($payload['guest_push_token'])) {
            $guestPushCallbackId = $this->guestPushCallbackService->register($guestDeviceId, $payload, $driverApp)->id;
        }

        $statusRequest = MasterStatusRequest::create([
            'driver_user_id' => $driver?->id,
            'master_id' => $master->id,
            'status' => 'pending',
            'channel' => 'sms',
            'expires_at' => now()->addMinutes(self::REQUEST_TIMEOUT_MINUTES),
            'meta' => array_filter([
                'token' => $token,
                'guest_device_id' => $guestDeviceId,
                'guest_push_callback_id' => $guestPushCallbackId,
            ]),
        ]);

        $appInstalled = $master->user && $this->fcmService->hasActiveTokens($master->user, $masterApp);
        $channel = $appInstalled ? 'push' : 'sms';
        $statusRequest->forceFill(['channel' => $channel])->save();

        $link = route('status-request.show', ['token' => $token]);
        $pushSent = false;
        if ($appInstalled && $master->user) {
            $pushSent = $this->fcmService->sendToUser(
                $master->user,
                'Новий запит!',
                'Клієнт хоче приїхати прямо зараз. Ви вільні?',
                [
                    'type' => 'master_status_request',
                    'request_id' => $statusRequest->id,
                    'master_id' => $master->id,
                    'link' => $link,
                ],
                $masterApp
            );
        }

        if (! $pushSent && ! empty($master->phone)) {
            $brandName = $this->brandName($driverApp);
            $text = "Клієнт на {$brandName} запитує, чи вільні ви зараз? Відповідь тут: {$link}";
            $this->sendPlainSms($master->phone, $text, $link, $brandName);
            $statusRequest->forceFill(['channel' => 'sms'])->save();
        }

        ExpireMasterStatusRequestJob::dispatch($statusRequest->id)
            ->delay($statusRequest->expires_at);

        return [
            'status' => 'sent',
            'request_id' => $statusRequest->id,
            'channel' => $statusRequest->channel,
            'cooldown_expires_at' => $statusRequest->created_at->copy()->addMinutes(self::DRIVER_COOLDOWN_MINUTES)->toISOString(),
            'expires_at' => $statusRequest->expires_at?->toISOString(),
        ];
    }

    public function respond(MasterStatusRequest $statusRequest, string $answer, string $source = 'app'): array
    {
        $statusRequest->loadMissing(['driver', 'master.user']);

        if ($statusRequest->status !== 'pending') {
            return [
                'status' => $statusRequest->status,
                'answer' => $statusRequest->answer,
            ];
        }

        if ($statusRequest->expires_at && $statusRequest->expires_at->isPast()) {
            return $this->expire($statusRequest);
        }

        $master = $statusRequest->master;
        $statusRequest->forceFill([
            'status' => 'answered',
            'answer' => $answer,
            'responded_at' => now(),
            'meta' => array_merge($statusRequest->meta ?? [], ['source' => $source]),
        ])->save();

        if ($answer === 'free') {
            $master->forceFill([
                'status' => 'green',
                'status_expires_at' => now()->addMinutes(self::AVAILABLE_FOR_MINUTES),
            ])->save();

            $this->availabilityService->setAvailable(
                $master->id,
                self::AVAILABLE_FOR_MINUTES,
                now()->toISOString(),
                $master->app
            );

            $message = "Майстер {$master->name} вільний і чекає на вас!";
        } else {
            $master->forceFill([
                'status' => 'gray',
                'status_expires_at' => null,
            ])->save();

            $this->availabilityService->setUnavailable($master->id, $master->app);
            $message = 'На жаль, майстер зараз зайнятий';
        }

        $this->notifyDriver($statusRequest->driver, $message, $statusRequest, $answer, (string) ($statusRequest->driver?->app ?? $master->app ?? 'carbeat'));

        return [
            'status' => 'answered',
            'answer' => $answer,
            'driver_message' => $message,
        ];
    }

    public function expire(MasterStatusRequest $statusRequest): array
    {
        $statusRequest->loadMissing(['driver', 'master']);

        if ($statusRequest->status !== 'pending') {
            return [
                'status' => $statusRequest->status,
                'answer' => $statusRequest->answer,
            ];
        }

        $statusRequest->forceFill([
            'status' => 'expired',
            'notification_message' => 'Майстер не відповів, спробуйте інше СТО',
        ])->save();

        $this->notifyDriver(
            $statusRequest->driver,
            'Майстер не відповів, спробуйте інше СТО',
            $statusRequest,
            'timeout',
            (string) ($statusRequest->driver?->app ?? $statusRequest->master->app ?? 'carbeat')
        );

        return [
            'status' => 'expired',
            'driver_message' => 'Майстер не відповів, спробуйте інше СТО',
        ];
    }

    private function notifyDriver(
        ?User $driver,
        string $message,
        MasterStatusRequest $statusRequest,
        string $answer,
        string $driverApp
    ): void {
        if ($driver) {
            $this->fcmService->sendToUser(
                $driver,
                $this->brandName($driverApp),
                $message,
                [
                    'type' => 'driver_status_request_update',
                    'request_id' => $statusRequest->id,
                    'master_id' => $statusRequest->master_id,
                    'driver_user_id' => $driver->id,
                    'answer' => $answer,
                    'message' => $message,
                ],
                $driverApp
            );
        } else {
            $this->guestPushCallbackService->consume(
                data_get($statusRequest->meta, 'guest_push_callback_id'),
                $this->brandName($driverApp),
                $message,
                [
                    'type' => 'driver_status_request_update',
                    'request_id' => $statusRequest->id,
                    'master_id' => $statusRequest->master_id,
                    'answer' => $answer,
                    'message' => $message,
                ],
                $driverApp,
                $this->fcmService
            );
        }

        $this->realtimePublisher->publishStatusRequestUpdate([
            'type' => 'status_request_update',
            'request_id' => $statusRequest->id,
            'master_id' => $statusRequest->master_id,
            'driver_user_id' => $driver?->id,
            'answer' => $answer,
            'message' => $message,
            'flavor' => $driver?->app ?? $statusRequest->master->app ?? 'carbeat',
        ]);
    }

    private function findRecentRequest(?User $driver, int $masterId, ?string $guestDeviceId, $cooldownThreshold): ?MasterStatusRequest
    {
        $query = MasterStatusRequest::query()
            ->where('master_id', $masterId)
            ->where('created_at', '>=', $cooldownThreshold)
            ->latest('id');

        if ($driver) {
            return $query
                ->where('driver_user_id', $driver->id)
                ->first();
        }

        if ($guestDeviceId) {
            return $query
                ->whereNull('driver_user_id')
                ->where('meta->guest_device_id', $guestDeviceId)
                ->first();
        }

        return null;
    }

    private function normalizeGuestDeviceId(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $normalized = trim($value);

        return $normalized !== '' ? $normalized : null;
    }

    private function sendPlainSms(string $phone, string $text, string $link, string $brandName): void
    {
        if (app()->environment('local')) {
            $this->sendTelegramFallback($phone, $link, $brandName);

            return;
        }

        try {
            \Daaner\TurboSMS\Facades\TurboSMS::sendMessages($phone, $text);
        } catch (\Throwable $e) {
            logger()->warning('Status request SMS failed', ['error' => $e->getMessage()]);
        }
    }

    private function sendTelegramFallback(string $phone, string $link, string $brandName): void
    {
        $message = sprintf(
            "🔔 <b>Local status request fallback</b>\n\n<b>Brand:</b> %s\n<b>Master phone:</b> <code>%s</code>\n<b>Reply link:</b> %s",
            e($brandName),
            e($phone),
            e($link)
        );

        try {
            $this->telegramService->send($message);
        } catch (\Throwable $e) {
            logger()->warning('Status request Telegram fallback failed', ['error' => $e->getMessage()]);
        }
    }

    private function brandName(string $app): string
    {
        return match (AppBrand::fromHeader($app)) {
            AppBrand::FLOXCITY => 'FloxCity',
            default => 'CarBeat',
        };
    }
}
