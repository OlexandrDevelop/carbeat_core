<?php

namespace App\Http\Services\Master;

use App\Http\Services\Appointment\AppointmentRedisService;
use Illuminate\Support\Carbon;

class MasterAvailabilityService
{
    public function __construct(
        private readonly AppointmentRedisService $appointmentRedisService
    ) {}

    /**
     * Calculate TTL and expiry timestamp for availability.
     *
     * @return array{ttl: int|null, expires_at: int|null}
     */
    public function calculateAvailabilityTtl(?int $durationMinutes, ?string $startTimeRaw): array
    {
        $ttlSeconds = null;
        $expiresAtTimestamp = null;

        if ($durationMinutes !== null) {
            if ($startTimeRaw) {
                try {
                    $start = Carbon::parse($startTimeRaw);
                    $expiresAt = $start->copy()->addMinutes($durationMinutes);
                    $expiresAtTimestamp = $expiresAt->timestamp;
                    $delta = $expiresAt->timestamp - now()->timestamp;
                    $ttlSeconds = $delta > 0 ? $delta : 1; // ensure positive TTL
                } catch (\Throwable $_) {
                    $ttlSeconds = max(1, $durationMinutes * 60);
                    $expiresAtTimestamp = now()->addSeconds($ttlSeconds)->timestamp;
                }
            } else {
                $ttlSeconds = max(1, $durationMinutes * 60);
                $expiresAtTimestamp = now()->addSeconds($ttlSeconds)->timestamp;
            }
        }

        return [
            'ttl' => $ttlSeconds,
            'expires_at' => $expiresAtTimestamp,
        ];
    }

    /**
     * Set master as available.
     */
    public function setAvailable(int $masterId, ?int $durationMinutes = null, ?string $startTime = null): void
    {
        $calculated = $this->calculateAvailabilityTtl($durationMinutes, $startTime);
        
        $this->appointmentRedisService->setAvailableFlag(
            $masterId,
            $calculated['ttl'],
            $calculated['expires_at']
        );
    }

    /**
     * Set master as unavailable.
     */
    public function setUnavailable(int $masterId): void
    {
        $this->appointmentRedisService->setUnavailableFlag($masterId);
    }

    /**
     * Get master availability status.
     */
    public function getAvailability(int $masterId): bool
    {
        return $this->appointmentRedisService->isAvailableFlag($masterId);
    }
}

