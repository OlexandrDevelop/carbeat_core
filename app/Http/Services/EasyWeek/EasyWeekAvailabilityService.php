<?php

namespace App\Http\Services\EasyWeek;

use App\Exceptions\EasyWeekApiException;
use App\Http\Services\Appointment\AppointmentRedisService;
use App\Models\EasyweekConnection;
use App\Models\Master;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Infers whether a master is busy right now from EasyWeek's own calendar,
 * and feeds that into the app's existing available/unavailable indicator
 * (the same Redis flag + status columns SmartRandomStatusService drives for
 * unclaimed masters) — no separate UI needed, the map/profile card already
 * render from these.
 *
 * The Partner API's /calendars endpoint returns free (bookable) spots for a
 * date. If "now" falls inside a free spot, nobody is booked with the master
 * at this moment -> available. If "now" isn't covered by any spot during
 * working hours, EasyWeek has them booked with a client right now -> busy.
 */
class EasyWeekAvailabilityService
{
    // Self-expiring: if the poll job stops running, the flag lapses instead
    // of leaving a master stuck showing "available" forever.
    private const FLAG_TTL_SECONDS = 900;

    public function __construct(
        private readonly EasyWeekPartnerApiClient $client,
        private readonly AppointmentRedisService $appointmentRedisService,
    ) {}

    public function sync(EasyweekConnection $connection): void
    {
        if (! $connection->primary_branch_id || ! $connection->primary_product_id) {
            return;
        }

        $master = Master::withoutGlobalScope('app')->find($connection->master_id);
        if (! $master) {
            return;
        }

        $timezone = $connection->timezone ?: 'UTC';
        $now = Carbon::now($timezone);
        $today = $now->toDateString();

        try {
            $response = $this->client->getCalendarSlots(
                $connection->easyweek_company_slug,
                $today,
                $today,
                $connection->primary_product_id,
                $connection->primary_branch_id
            );
        } catch (EasyWeekApiException $e) {
            Log::warning('EasyWeek availability poll failed', [
                'connection_id' => $connection->id,
                'error' => $e->getMessage(),
            ]);

            return;
        }

        $isFree = $this->isFreeRightNow($response['data']['days'] ?? [], $today, $now, $timezone);

        $this->applyStatus($master, $isFree);

        $connection->update(['last_availability_synced_at' => now()]);
    }

    private function isFreeRightNow(array $days, string $today, Carbon $now, string $timezone): bool
    {
        foreach ($days as $day) {
            if (($day['date'] ?? null) !== $today) {
                continue;
            }

            foreach ($day['spots'] ?? [] as $spot) {
                $start = $this->parseSpotTime($today, $spot['start'] ?? null, $timezone);
                $end = $this->parseSpotTime($today, $spot['end'] ?? null, $timezone);

                if ($start && $end && $now->betweenIncluded($start, $end)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * EasyWeek's public spec doesn't pin down whether spot times are bare
     * "HH:MM" or full ISO datetimes, so handle both.
     */
    private function parseSpotTime(string $date, ?string $time, string $timezone): ?Carbon
    {
        if (! $time) {
            return null;
        }

        try {
            return str_contains($time, $date) || str_contains($time, 'T')
                ? Carbon::parse($time, $timezone)
                : Carbon::parse("{$date} {$time}", $timezone);
        } catch (Throwable) {
            return null;
        }
    }

    private function applyStatus(Master $master, bool $isFree): void
    {
        $master->forceFill([
            'status' => $isFree ? 'green' : 'gray',
            'status_expires_at' => null,
            'is_fake_online' => false,
            'last_status_update' => now(),
            'available' => $isFree,
        ])->save();

        if ($isFree) {
            $this->appointmentRedisService->setAvailableFlag(
                $master->id,
                self::FLAG_TTL_SECONDS,
                null,
                $master->app
            );
        } else {
            $this->appointmentRedisService->setUnavailableFlag($master->id, $master->app);
        }
    }
}
