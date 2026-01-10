<?php

namespace App\Http\Services;

use App\Http\Services\Appointment\AppointmentRedisService;
use App\Models\Booking;
use App\Models\Master;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;

class BookingService
{
    public function __construct(
        private readonly AppointmentRedisService $redisService
    ) {}

    public function createBooking(int $masterId, ?int $clientId, Carbon $start, Carbon $end, string $note = null): Booking
    {
        // Ensure master exists and belongs to request country
        $countryId = (int) config('app.country_id');
        Master::where('id', $masterId)->where('country_id', $countryId)->firstOrFail();

        // Check slot is fully inside free interval
        if (! $this->redisService->isIntervalFree($masterId, $start, $end)) {
            abort(422, 'Requested time is not available');
        }

        // Prevent overlaps in DB for pending/confirmed
        $overlap = Booking::query()
            ->where('master_id', $masterId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($q) use ($start, $end) {
                $q->where('start_time', '<', $end)
                  ->where('end_time', '>', $start);
            })
            ->exists();

        if ($overlap) {
            abort(409, 'Slot already booked');
        }

        $booking = Booking::create([
            'master_id' => $masterId,
            'client_id' => $clientId,
            'start_time' => $start,
            'end_time' => $end,
            'status' => 'pending',
            'note' => $note,
        ]);

        return $booking;
    }

    public function listBookings(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        $q = Booking::query()->with(['master', 'client']);

        if (! empty($filters['master_id'])) {
            $q->where('master_id', (int) $filters['master_id']);
        }
        if (! empty($filters['status'])) {
            $q->where('status', $filters['status']);
        }
        if (! empty($filters['date'])) {
            $date = Carbon::parse($filters['date']);
            $q->whereDate('start_time', $date->toDateString());
        }

        return $q->orderBy('start_time', 'asc')->paginate($perPage);
    }

    public function updateStatus(Booking $booking, string $status): Booking
    {
        $allowed = ['confirmed', 'cancelled'];
        if (! in_array($status, $allowed, true)) {
            abort(422, 'Invalid status');
        }
        $booking->status = $status;
        $booking->save();

        if ($status === 'confirmed') {
            // Mark busy in Redis to reduce race windows
            $this->redisService->markAsBusy(
                $booking->master_id,
                Carbon::parse($booking->start_time),
                Carbon::parse($booking->end_time)
            );
        }

        return $booking->refresh();
    }

    /**
     * Generate available slots for a master for a specific day and slot duration.
     * This is based on the free intervals in Redis minus DB bookings.
     */
    public function getAvailableSlots(int $masterId, Carbon $date, int $durationMinutes): array
    {
        $dayStart = (clone $date)->startOfDay();
        $dayEnd = (clone $date)->endOfDay();

        $intervals = $this->redisService->getFreeIntervals($masterId, $dayStart, $dayEnd);
        $duration = $durationMinutes * 60; // seconds

        // Fetch existing bookings for the day
        $bookings = Booking::query()
            ->where('master_id', $masterId)
            ->whereIn('status', ['pending', 'confirmed'])
            ->whereBetween('start_time', [$dayStart, $dayEnd])
            ->get(['start_time', 'end_time']);

        $busyRanges = [];
        foreach ($bookings as $b) {
            $busyRanges[] = [
                'start' => Carbon::parse($b->start_time)->timestamp,
                'end' => Carbon::parse($b->end_time)->timestamp,
            ];
        }

        $slots = [];
        foreach ($intervals as $interval) {
            $slotStart = max($interval['start'], $dayStart->timestamp);
            $intervalEnd = min($interval['end'], $dayEnd->timestamp);

            while ($slotStart + $duration <= $intervalEnd) {
                $slotEnd = $slotStart + $duration;

                if (! $this->overlapsAny($slotStart, $slotEnd, $busyRanges)) {
                    $slots[] = [
                        'start_time' => Carbon::createFromTimestamp($slotStart)->toIso8601String(),
                        'end_time' => Carbon::createFromTimestamp($slotEnd)->toIso8601String(),
                    ];
                }

                $slotStart += $duration; // step by duration
            }
        }

        return $slots;
    }

    private function overlapsAny(int $start, int $end, array $ranges): bool
    {
        foreach ($ranges as $r) {
            if ($start < (int) $r['end'] && $end > (int) $r['start']) {
                return true;
            }
        }
        return false;
    }
}
