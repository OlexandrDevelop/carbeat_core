<?php

namespace App\Http\Services\Appointment;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;
use App\Models\Master;
use App\Enums\AppBrand;

class AppointmentRedisService
{

    //check if the master is available at the given time (now unused param kept for BC)
    public function getAvailability(int $masterId, Carbon $checkTime): bool
    {
        return $this->isMasterAvailableAt($masterId, $checkTime);
    }

    // ---- Availability FLAG (free/busy) ----
    // Cache for flavor/app values to avoid repeated lookups
    private static array $flavorCache = [];

    /**
     * Get availability flag key for a master.
     * Optionally accepts the master's app/flavor to avoid DB lookups.
     *
     * @param int $masterId The master ID
     * @param string|null $flavor The master's app/flavor. If null, uses default config value.
     * @return string The Redis key for this master's availability flag
     */
    public function getAvailabilityFlagKey(int $masterId, ?string $flavor = null): string
    {
        if ($flavor === null) {
            // Fallback to config default if flavor not provided
            $flavor = $this->getDefaultFlavor();
        }

        return "{$flavor}:master:{$masterId}:available";
    }

    /**
     * Get the default flavor from config
     */
    private function getDefaultFlavor(): string
    {
        if (isset(self::$flavorCache['__default__'])) {
            return self::$flavorCache['__default__'];
        }

        $cfg = config('app.client');
        $flavor = 'carbeat'; // ultimate default

        if ($cfg instanceof AppBrand) {
            $flavor = $cfg->value;
        } elseif (is_string($cfg) && $cfg !== '') {
            $flavor = $cfg;
        }

        self::$flavorCache['__default__'] = $flavor;
        return $flavor;
    }

    public function setAvailableFlag(int $masterId, ?int $ttlSeconds = null, ?int $expiresAtTimestamp = null, ?string $flavor = null): void
    {
        $flavor = $flavor ?? $this->getDefaultFlavor();
        $effectiveTtl = $ttlSeconds ?? (int) env('AVAILABILITY_TTL_SECONDS', 3600);

        if ($effectiveTtl > 0) {
            Redis::set($this->getAvailabilityFlagKey($masterId, $flavor), 1, 'EX', $effectiveTtl);
        } else {
            Redis::set($this->getAvailabilityFlagKey($masterId, $flavor), 1);
        }

        $finalExpiresAt = $expiresAtTimestamp;
        if ($finalExpiresAt === null && $effectiveTtl > 0) {
            $finalExpiresAt = now()->addSeconds($effectiveTtl)->timestamp;
        }

        $this->publishAvailabilityEvent($masterId, true, $finalExpiresAt, $flavor);
    }

    public function setUnavailableFlag(int $masterId, ?string $flavor = null): void
    {
        $flavor = $flavor ?? $this->getDefaultFlavor();
        Redis::del($this->getAvailabilityFlagKey($masterId, $flavor));
        $this->publishAvailabilityEvent($masterId, false, null, $flavor);
    }

    public function isAvailableFlag(int $masterId, ?string $flavor = null): bool
    {
        $flavor = $flavor ?? $this->getDefaultFlavor();
        return (bool) Redis::exists($this->getAvailabilityFlagKey($masterId, $flavor));
    }

    private function publishAvailabilityEvent(int $masterId, bool $available, ?int $expiresAt, ?string $flavor = null): void
    {
        $flavor = $flavor ?? $this->getDefaultFlavor();
        $payloadArr = [
            'id' => $masterId,
            'available' => $available,
            'expiresAt' => $expiresAt,
            'ts' => now()->timestamp,
            'flavor' => $flavor,
        ];
        $payload = json_encode($payloadArr);
        try {
            $channel = $flavor . ':availability:events';
            Redis::publish($channel, $payload);
        } catch (\Throwable $e) {
            // Intentionally ignore publish errors to avoid breaking main flow
        }
    }

    public function getAvailabilityFlagsForMany(array $masterIds, ?array $masterAppMap = null): array
    {
        $availability = [];
        /** @phpstan-ignore-next-line */
        $results = Redis::pipeline(function ($pipe) use ($masterIds, $masterAppMap) {
            foreach ($masterIds as $masterId) {
                // Get flavor from masterAppMap if provided, otherwise use default
                $flavor = null;
                if ($masterAppMap !== null && isset($masterAppMap[$masterId])) {
                    $flavor = $masterAppMap[$masterId];
                }
                $pipe->exists($this->getAvailabilityFlagKey($masterId, $flavor));
            }
        });
        foreach ($masterIds as $index => $masterId) {
            $availability[$masterId] = (bool) ($results[$index] ?? false);
        }
        return $availability;
    }

    // ---- Legacy signature kept, now delegates to flag ----
    public function isMasterAvailableAt(int $masterId, Carbon $checkTime): bool
    {
        return $this->isAvailableFlag($masterId);
    }

    // ---- Booking intervals (kept for booking module only) ----
    public function getMasterBusyIntervalsKey(int $masterId): string
    {
        return "master:{$masterId}:busy_intervals";
    }

    public function getMasterFreeIntervalsKey(int $masterId): string
    {
        return "master:{$masterId}:free_intervals";
    }

    public function clearExpiredIntervals(int $masterId): void
    {
        Redis::zremrangebyscore(
            $this->getMasterBusyIntervalsKey($masterId),
            '-inf',
            now()->timestamp - 3600 * 24
        );
        Redis::zremrangebyscore(
            $this->getMasterFreeIntervalsKey($masterId),
            '-inf',
            now()->timestamp - 3600 * 24 // наприклад, очищає інтервали старші 24 год
        );
    }

    public function markAsBusy(int $masterId, Carbon $startTime, Carbon $endTime): void
    {
        $this->clearExpiredIntervals($masterId);
        Redis::zadd(
            $this->getMasterBusyIntervalsKey($masterId),
            $startTime->timestamp,
            json_encode([
                'start' => $startTime->timestamp,
                'end' => $endTime->timestamp,
            ])
        );
    }

    public function markAsUnavailableFromNow(int $masterId): void
    {
        $key = $this->getMasterFreeIntervalsKey($masterId);
        $freeIntervals = Redis::zrangebyscore($key, '-inf', '+inf');

        foreach ($freeIntervals as $rawInterval) {
            $interval = json_decode($rawInterval, true);

            if (! $interval || ! isset($interval['start'], $interval['end'])) {
                continue;
            }

            $start = (int) $interval['start'];
            $end = (int) $interval['end'];

            // If the current time is within the interval
            $now = Carbon::now();
            if ($now->timestamp >= $start && $now->timestamp < $end) {
                // Delete the current interval
                Redis::zrem($key, json_encode($interval));

                // Add a new interval that starts from now
                if ($now->timestamp > $start) {
                    $newInterval = [
                        'start' => $start,
                        'end' => $now->timestamp,
                    ];

                    Redis::zadd($key, $newInterval['start'], json_encode($newInterval));
                }

                return;
            }
        }
    }

    // NOTE: kept only for booking schedule management; availability flag endpoint no longer calls this
    public function markAsFree(int $masterId, Carbon $startTime, Carbon $endTime): void
    {
        $this->clearExpiredIntervals($masterId);
        Redis::zadd(
            $this->getMasterFreeIntervalsKey($masterId),
            $startTime->timestamp,
            json_encode([
                'start' => $startTime->timestamp,
                'end' => $endTime->timestamp,
            ])
        );
    }

    public function getAvailabilityForMany(array $masterIds, Carbon $checkTime): array
    {
        // Now returns simple flags map
        return $this->getAvailabilityFlagsForMany($masterIds);
    }

    public function getFreeIntervals(int $masterId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $fromScore = $from ? $from->timestamp : '-inf';
        $toScore = $to ? $to->timestamp : '+inf';

        $raw = Redis::zrangebyscore(
            $this->getMasterFreeIntervalsKey($masterId),
            $fromScore,
            $toScore
        );

        $intervals = [];
        foreach ($raw as $r) {
            $decoded = json_decode($r, true);
            if (isset($decoded['start'], $decoded['end'])) {
                $intervals[] = $decoded;
            }
        }

        return $intervals;
    }

    public function isIntervalFree(int $masterId, Carbon $start, Carbon $end): bool
    {
        $intervals = $this->getFreeIntervals($masterId);
        $s = $start->timestamp;
        $e = $end->timestamp;
        foreach ($intervals as $interval) {
            if ($s >= (int) $interval['start'] && $e <= (int) $interval['end']) {
                return true;
            }
        }
        return false;
    }

    private function isTimestampInFreeIntervals(array $intervals, int $timestamp): bool
    {
        foreach ($intervals as $key => $value) {
            if (is_numeric($key)) {
                if ($key % 2 !== 0) {
                    continue; // skip score
                }
                $interval = json_decode($value, true);
            } else {
                $interval = json_decode($key, true);
            }

            if (! $interval || ! isset($interval['start'], $interval['end'])) {
                continue;
            }

            if ($timestamp >= $interval['start'] && $timestamp < $interval['end']) {
                return true;
            }
        }

        return false;
    }
}
