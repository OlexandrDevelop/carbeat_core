<?php

namespace App\Http\Services;

use App\Helpers\PhoneHelper;
use App\Models\Master;
use App\Models\RepairRequest;

/**
 * Notifies masters offering the matching service about a new repair
 * request: free via Telegram if already linked (App\Http\Services\MasterTelegramService),
 * otherwise a paid SMS invite to link the bot — capped at
 * SMS_INVITE_LIMIT per master so an unresponsive master doesn't keep
 * costing money forever.
 */
class RepairRequestNotificationService
{
    public const SMS_INVITE_LIMIT = 3;

    /**
     * Same radius the driver's own map search uses by default — a master
     * further than this from the request's city isn't a realistic option
     * for a breakdown repair.
     */
    private const RADIUS_KM = 50;

    public function __construct(
        private readonly MasterTelegramService $telegramService,
        private readonly SmsService $smsService,
    ) {}

    public function notify(RepairRequest $repairRequest): void
    {
        foreach ($this->matchingMasters($repairRequest)->get() as $master) {
            $this->notifyMaster($master, $repairRequest);
        }
    }

    /**
     * The same service+radius matching query used to decide who gets
     * notified — exposed publicly so the admin panel
     * (App\Http\Controllers\Admin\RepairRequestController) can show exactly
     * which masters (and cities) a request was matched against, without
     * duplicating the logic.
     *
     * @return \Illuminate\Database\Eloquent\Builder<Master>
     */
    public function matchingMasters(RepairRequest $repairRequest)
    {
        $query = Master::where(function ($query) use ($repairRequest) {
            if ($repairRequest->service_id) {
                $query->where('service_id', $repairRequest->service_id)
                    ->orWhereHas('services', fn ($q) => $q->where('services.id', $repairRequest->service_id));
            } else {
                // "Інше" — no service to match against, so no query would
                // otherwise be built (leaving an always-true empty where()).
                $query->whereRaw('1 = 0');
            }
        })
            ->whereNotNull('contact_phone');

        if ($repairRequest->latitude !== null && $repairRequest->longitude !== null) {
            $this->constrainToRadius($query, $repairRequest->latitude, $repairRequest->longitude);
        }

        return $query;
    }

    /**
     * Bounding-box pre-filter + exact Haversine distance, mirroring
     * App\Http\Services\Master\MasterSearchService::queryMastersOnDistance()
     * so "within 50km" means the same thing for drivers and masters.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<Master>  $query
     */
    private function constrainToRadius($query, float $lat, float $lng): void
    {
        $latDelta = self::RADIUS_KM / 111; // 111 км ≈ 1° широти
        $lngDelta = self::RADIUS_KM / (111 * cos(deg2rad($lat))); // Δ довготи залежить від широти

        $query->selectRaw('masters.*')
            ->selectRaw(
                '(6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) as distance',
                [$lat, $lng, $lat]
            )
            ->whereNotNull('latitude')
            ->whereNotNull('longitude')
            ->whereBetween('latitude', [$lat - $latDelta, $lat + $latDelta])
            ->whereBetween('longitude', [$lng - $lngDelta, $lng + $lngDelta])
            ->havingRaw('distance <= ?', [self::RADIUS_KM]);
    }

    private function notifyMaster(Master $master, RepairRequest $repairRequest): void
    {
        if ($master->telegram_chat_id) {
            $this->telegramService->notify($master, $this->buildTelegramMessage($repairRequest));

            return;
        }

        if (! PhoneHelper::isMobile((string) $master->contact_phone)) {
            // Landlines / toll-free numbers can never receive the SMS invite,
            // so they can never complete the opt-in funnel — nothing to do.
            return;
        }

        if ($master->repair_request_sms_invite_count >= self::SMS_INVITE_LIMIT) {
            return;
        }

        $link = $this->telegramService->buildDeepLink($master);
        $sent = $this->smsService->sendPlainText($master->contact_phone, $this->buildSmsText($link));

        if ($sent) {
            $master->forceFill([
                'repair_request_sms_invite_count' => $master->repair_request_sms_invite_count + 1,
                'repair_request_sms_invite_last_sent_at' => now(),
                // Remembered so the Telegram bot's /start confirmation can
                // show the request that actually prompted this SMS — the
                // deep link itself is reused across invites, so the token
                // alone can't tell us which request triggered any given click.
                'last_invited_repair_request_id' => $repairRequest->id,
            ])->save();
        }
    }

    public function buildTelegramMessage(RepairRequest $repairRequest): string
    {
        return sprintf(
            "🔧 <b>Нова заявка на ремонт</b>\n\n<b>Авто:</b> %s\n<b>Проблема:</b> %s\n<b>Клієнт:</b> %s, %s\n\n<a href=\"%s\">Переглянути заявку в кабінеті Carbeat</a>",
            e($this->formatCar($repairRequest)),
            e($repairRequest->description),
            e($repairRequest->name),
            e($repairRequest->phone),
            e(route('master.repair_requests.index', ['selected' => $repairRequest->id]))
        );
    }

    /**
     * car_model/car_year are optional (a driver often doesn't know either) —
     * only append what's actually present instead of leaving dangling
     * "Toyota  ()" gaps in the message.
     */
    private function formatCar(RepairRequest $repairRequest): string
    {
        $parts = array_filter([$repairRequest->car_make, $repairRequest->car_model]);
        $car = implode(' ', $parts);

        return $repairRequest->car_year ? "{$car} ({$repairRequest->car_year})" : $car;
    }

    /**
     * Kept to a single UCS-2 SMS segment (<=70 Cyrillic chars) to avoid
     * paying for a second segment — see MasterTelegramService::buildDeepLink
     * for why the token/link itself is kept short. With an 8-char token and
     * a ~10-char bot username this leaves comfortable room; if the bot's
     * registered username ends up long, shorten this lead-in further.
     */
    private function buildSmsText(string $link): string
    {
        return "Заявка на ремонт авто: {$link}";
    }
}
