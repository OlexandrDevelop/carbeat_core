<?php

namespace App\Http\Services\Appointment;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Redis;

class AppointmentRedisService
{

    //check if the master is available at the given time (now unused param kept for BC)
    public function getAvailability(int $masterId, Carbon $checkTime): bool
    {
        return $this->isMasterAvailableAt($masterId, $checkTime);
    }

    // ---- Availability FLAG (free/busy) ----
    public function getAvailabilityFlagKey(int $masterId): string
    {
        return "master:{$masterId}:available";
    }

    public function setAvailableFlag(int $masterId): void
    {
        Redis::set($this->getAvailabilityFlagKey($masterId), 1);
    }

    public function setUnavailableFlag(int $masterId): void
    {
        Redis::del($this->getAvailabilityFlagKey($masterId));
    }

    public function isAvailableFlag(int $masterId): bool
    {
        return (bool) Redis::exists($this->getAvailabilityFlagKey($masterId));
    }

    public function getAvailabilityFlagsForMany(array $masterIds): array
    {
        $availability = [];
        /** @phpstan-ignore-next-line */
        $results = Redis::pipeline(function ($pipe) use ($masterIds) {
            foreach ($masterIds as $masterId) {
                $pipe->exists($this->getAvailabilityFlagKey($masterId));
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
