<?php

namespace App\Http\Services;

use App\Http\Services\Appointment\AppointmentRedisService;
use App\Models\MasterWorkSchedule;
use App\Models\MasterTimeOff;
use Carbon\Carbon;

class ScheduleService
{
    public function __construct(private readonly AppointmentRedisService $redis)
    {
    }

    /**
     * Compute free intervals for a given date from weekly rules, minus time-off.
     */
    public function computeDayIntervals(int $masterId, Carbon $date): array
    {
        $dayOfWeek = (int) $date->dayOfWeek; // 0..6

        $rules = MasterWorkSchedule::query()
            ->where('master_id', $masterId)
            ->where('day_of_week', $dayOfWeek)
            ->where('active', true)
            ->get();

        $intervals = [];
        foreach ($rules as $r) {
            $start = Carbon::parse($date->toDateString().' '.$r->start_time);
            $end = Carbon::parse($date->toDateString().' '.$r->end_time);
            if ($end->lessThanOrEqualTo($start)) {
                continue;
            }
            $intervals[] = ['start' => $start->clone(), 'end' => $end->clone()];
        }

        // Subtract time-off
        $offs = MasterTimeOff::query()
            ->where('master_id', $masterId)
            ->whereDate('start_time', '<=', $date)
            ->whereDate('end_time', '>=', $date)
            ->get();

        foreach ($offs as $off) {
            $intervals = $this->subtractInterval($intervals, Carbon::parse($off->start_time), Carbon::parse($off->end_time));
        }

        // Normalize to unix timestamps
        return array_values(array_map(fn ($i) => [
            'start' => $i['start']->timestamp,
            'end' => $i['end']->timestamp,
        ], $intervals));
    }

    /**
     * Push computed intervals for a date to Redis free intervals set (booking engine reads from here).
     */
    public function syncDayToRedis(int $masterId, Carbon $date): void
    {
        $intervals = $this->computeDayIntervals($masterId, $date);
        // clear existing free intervals for that day range
        // naive approach: remove day range and re-add
        $from = $date->copy()->startOfDay();
        $to = $date->copy()->endOfDay();

        // There is no dedicated delete-range, so we can fetch existing and remove one by one
        $existing = $this->redis->getFreeIntervals($masterId, $from, $to);
        foreach ($existing as $ex) {
            \Illuminate\Support\Facades\Redis::zrem(
                $this->redis->getMasterFreeIntervalsKey($masterId),
                json_encode($ex)
            );
        }

        // add new
        foreach ($intervals as $i) {
            $this->redis->markAsFree($masterId, Carbon::createFromTimestamp($i['start']), Carbon::createFromTimestamp($i['end']));
        }
    }

    private function subtractInterval(array $intervals, Carbon $offStart, Carbon $offEnd): array
    {
        $result = [];
        foreach ($intervals as $i) {
            $s = $i['start'];
            $e = $i['end'];
            // No overlap
            if ($offEnd->lessThanOrEqualTo($s) || $offStart->greaterThanOrEqualTo($e)) {
                $result[] = $i;
                continue;
            }
            // Cut left part
            if ($offStart->greaterThan($s) && $offStart->lessThan($e)) {
                $result[] = ['start' => $s->clone(), 'end' => $offStart->clone()];
            }
            // Cut right part
            if ($offEnd->greaterThan($s) && $offEnd->lessThan($e)) {
                $result[] = ['start' => $offEnd->clone(), 'end' => $e->clone()];
            }
            // If off covers entire interval, nothing to add
        }
        return $result;
    }
}
