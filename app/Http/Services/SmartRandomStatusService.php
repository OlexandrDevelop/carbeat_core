<?php

namespace App\Http\Services;

use App\Enums\AppBrand;
use App\Http\Services\Appointment\AppointmentRedisService;
use App\Models\AppSetting;
use App\Models\Master;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class SmartRandomStatusService
{
    private const ENABLED_KEY = 'smart_random_enabled';
    private const PERCENTAGE_KEY = 'smart_random_percentage';
    private const GLOBAL_START = '08:00';
    private const GLOBAL_END = '20:00';
    private const ROTATION_MIN_MINUTES = 30;
    private const ROTATION_MAX_MINUTES = 120;
    private const SYSTEM_USER_ID = 1;

    public function __construct(
        private readonly AppointmentRedisService $appointmentRedisService,
    ) {}

    public function getSettings(?string $app = null): array
    {
        $app ??= $this->resolveCurrentApp();

        return [
            'enabled' => (bool) ($this->getSettingValueForApp(self::ENABLED_KEY, $app) ?? false),
            'percentage' => $this->normalizePercentage(
                $this->getSettingValueForApp(self::PERCENTAGE_KEY, $app)
            ),
            'app' => $app,
            'global_window' => [
                'start' => self::GLOBAL_START,
                'end' => self::GLOBAL_END,
            ],
            'rotation_window_minutes' => [
                'min' => self::ROTATION_MIN_MINUTES,
                'max' => self::ROTATION_MAX_MINUTES,
            ],
        ];
    }

    public function updateSettings(array $data, ?string $app = null): array
    {
        $app ??= $this->resolveCurrentApp();

        if (array_key_exists('enabled', $data)) {
            AppSetting::updateOrCreate(
                ['key' => $this->getAppSpecificKey(self::ENABLED_KEY, $app)],
                ['value' => (bool) $data['enabled']]
            );
        }

        if (array_key_exists('percentage', $data)) {
            AppSetting::updateOrCreate(
                ['key' => $this->getAppSpecificKey(self::PERCENTAGE_KEY, $app)],
                ['value' => $this->normalizePercentage($data['percentage'])]
            );
        }

        return $this->getSettings($app);
    }

    public function getDashboardData(?string $app = null): array
    {
        $app ??= $this->resolveCurrentApp();

        return [
            'settings' => $this->getSettings($app),
            'stats' => $this->getStats($app),
            'fake_green_masters' => $this->listFakeGreenMasters($app),
        ];
    }

    public function getStats(?string $app = null): array
    {
        $app ??= $this->resolveCurrentApp();

        $baseQuery = $this->mastersForApp($app);

        return [
            'total_stos' => (clone $baseQuery)->count(),
            'fake_green' => (clone $baseQuery)
                ->where('status', 'green')
                ->where('is_fake_online', true)
                ->count(),
            'real_green' => (clone $baseQuery)
                ->where('status', 'green')
                ->where('is_fake_online', false)
                ->count(),
        ];
    }

    public function listFakeGreenMasters(?string $app = null): array
    {
        $app ??= $this->resolveCurrentApp();

        return $this->mastersForApp($app)
            ->where('status', 'green')
            ->where('is_fake_online', true)
            ->orderByDesc('last_status_update')
            ->get([
                'id',
                'name',
                'address',
                'contact_phone',
                'last_status_update',
            ])
            ->map(fn (Master $master) => [
                'id' => $master->id,
                'name' => $master->name,
                'address' => $master->address,
                'phone' => $master->phone,
                'last_status_update' => $master->last_status_update?->toISOString(),
            ])
            ->all();
    }

    public function turnOffFakeStatus(int $masterId, ?string $app = null): ?Master
    {
        $app ??= $this->resolveCurrentApp();

        $master = $this->mastersForApp($app)
            ->whereKey($masterId)
            ->where('is_fake_online', true)
            ->first();

        if (! $master) {
            return null;
        }

        $this->markFakeOffline(collect([$master]), now());

        return $master->refresh();
    }

    public function setManualStatus(Master $master, string $status, ?CarbonInterface $expiresAt = null): void
    {
        $master->forceFill([
            'status' => $status,
            'status_expires_at' => $expiresAt,
            'is_fake_online' => false,
            'last_status_update' => null,
        ])->save();
    }

    public function sync(?string $app = null, ?CarbonInterface $now = null): array
    {
        $app ??= $this->resolveCurrentApp();
        $now = $now ? Carbon::instance($now) : now();
        $settings = $this->getSettings($app);

        if (! $settings['enabled']) {
            $activeFake = $this->mastersForApp($app)
                ->where('status', 'green')
                ->where('is_fake_online', true)
                ->get();

            $this->markFakeOffline($activeFake, $now);

            return [
                'app' => $app,
                'enabled' => false,
                'target_fake_green' => 0,
                'current_fake_green' => 0,
                'turned_on' => 0,
                'turned_off' => $activeFake->count(),
            ];
        }

        $totalMasters = $this->mastersForApp($app)->count();
        $manualGreenCount = $this->mastersForApp($app)
            ->where('status', 'green')
            ->where('is_fake_online', false)
            ->count();

        $eligible = $this->candidateBaseQuery($app)
            ->get([
                'id',
                'name',
                'status',
                'working_hours',
                'is_fake_online',
                'last_status_update',
                'created_at',
            ]);

        $eligibleNow = $eligible->filter(fn (Master $master) => $this->isWithinActiveWindow($master, $now))->values();
        $outsideWindow = $eligible
            ->filter(fn (Master $master) => ! $this->isWithinActiveWindow($master, $now) && $master->is_fake_online && $master->status === 'green')
            ->values();

        $targetVisibleGreen = (int) round($totalMasters * ($settings['percentage'] / 100));
        $targetFakeGreen = max(0, min($eligibleNow->count(), $targetVisibleGreen - $manualGreenCount));

        $currentFakeGreen = $eligibleNow
            ->filter(fn (Master $master) => $master->is_fake_online && $master->status === 'green')
            ->values();

        $dueForRotation = $currentFakeGreen
            ->filter(fn (Master $master) => $this->isReadyForRotation($master, $now))
            ->values();

        $keepActive = $currentFakeGreen
            ->reject(fn (Master $master) => $dueForRotation->contains('id', $master->id))
            ->values();

        $turnOff = $outsideWindow->merge($dueForRotation)->unique('id')->values();

        if ($keepActive->count() > $targetFakeGreen) {
            $extraOff = $keepActive
                ->sortBy('last_status_update')
                ->take($keepActive->count() - $targetFakeGreen)
                ->values();

            $turnOff = $turnOff->merge($extraOff)->unique('id')->values();
            $keepActive = $keepActive
                ->reject(fn (Master $master) => $extraOff->contains('id', $master->id))
                ->values();
        }

        $activeAfterOff = max(0, $currentFakeGreen->count() - $turnOff->count());
        $needed = max(0, $targetFakeGreen - $activeAfterOff);

        $inactiveEligible = $eligibleNow
            ->reject(fn (Master $master) => $master->is_fake_online && $master->status === 'green')
            ->reject(fn (Master $master) => $turnOff->contains('id', $master->id))
            ->values();

        $readyToTurnOn = $inactiveEligible
            ->filter(fn (Master $master) => $this->isReadyForRotation($master, $now))
            ->shuffle()
            ->values();

        $turnOn = $readyToTurnOn->take($needed)->values();

        if ($turnOn->count() < $needed) {
            $fallback = $inactiveEligible
                ->reject(fn (Master $master) => $turnOn->contains('id', $master->id))
                ->sortBy('last_status_update')
                ->take($needed - $turnOn->count())
                ->values();

            $turnOn = $turnOn->merge($fallback)->unique('id')->values();
        }

        $this->markFakeOffline($turnOff, $now);
        $this->markFakeOnline($turnOn, $now);
        $this->ensureActiveFakeGreenAvailability($app);

        return [
            'app' => $app,
            'enabled' => true,
            'target_fake_green' => $targetFakeGreen,
            'current_fake_green' => max(0, $activeAfterOff + $turnOn->count()),
            'turned_on' => $turnOn->count(),
            'turned_off' => $turnOff->count(),
        ];
    }

    public function isWithinActiveWindow(Master $master, ?CarbonInterface $at = null): bool
    {
        $at = $at ? Carbon::instance($at) : now();
        $workingHours = $master->working_hours;

        if (is_array($workingHours) && ! empty($workingHours)) {
            $intervals = $this->extractIntervalsForDay($workingHours, $at);

            if ($intervals !== []) {
                foreach ($intervals as $interval) {
                    if ($this->isCurrentTimeInInterval($at, $interval['start'], $interval['end'])) {
                        return true;
                    }
                }

                return false;
            }
        }

        return $this->isCurrentTimeInInterval($at, self::GLOBAL_START, self::GLOBAL_END);
    }

    private function candidateBaseQuery(string $app): Builder
    {
        return $this->mastersForApp($app)
            ->where(function (Builder $query) {
                $query->whereNull('user_id')
                    ->orWhere('user_id', self::SYSTEM_USER_ID);
            })
            ->where('is_claimed', false);
    }

    private function mastersForApp(string $app): Builder
    {
        return Master::withoutGlobalScope('app')->where('app', $app);
    }

    private function markFakeOnline(Collection $masters, CarbonInterface $now): void
    {
        if ($masters->isEmpty()) {
            return;
        }

        $this->mastersForIds($masters)->update([
            'status' => 'green',
            'status_expires_at' => null,
            'is_fake_online' => true,
            'last_status_update' => $now,
            'updated_at' => $now,
        ]);

        foreach ($masters as $master) {
            $this->appointmentRedisService->setAvailableFlag(
                $master->id,
                0,
                null,
                $master->app ?? null
            );
        }
    }

    private function markFakeOffline(Collection $masters, CarbonInterface $now): void
    {
        if ($masters->isEmpty()) {
            return;
        }

        $this->mastersForIds($masters)->update([
            'status' => 'gray',
            'status_expires_at' => null,
            'is_fake_online' => false,
            'last_status_update' => $now,
            'updated_at' => $now,
        ]);

        foreach ($masters as $master) {
            $this->appointmentRedisService->setUnavailableFlag(
                $master->id,
                $master->app ?? null
            );
        }
    }

    private function mastersForIds(Collection $masters): Builder
    {
        return Master::withoutGlobalScope('app')->whereIn('id', $masters->pluck('id')->all());
    }

    private function isReadyForRotation(Master $master, CarbonInterface $now): bool
    {
        if (! $master->last_status_update) {
            return true;
        }

        return $master->last_status_update->copy()
            ->addMinutes($this->rotationDelayMinutes($master))
            ->lessThanOrEqualTo($now);
    }

    private function rotationDelayMinutes(Master $master): int
    {
        $seedSource = implode('|', [
            $master->id,
            $master->last_status_update?->timestamp ?? $master->created_at?->timestamp ?? 0,
            $master->status ?? 'gray',
        ]);

        $range = self::ROTATION_MAX_MINUTES - self::ROTATION_MIN_MINUTES;

        return self::ROTATION_MIN_MINUTES + (crc32($seedSource) % ($range + 1));
    }

    private function normalizePercentage(mixed $value): int
    {
        return max(0, min(100, (int) $value));
    }

    private function ensureActiveFakeGreenAvailability(string $app): void
    {
        $activeFakeGreens = $this->mastersForApp($app)
            ->where('status', 'green')
            ->where('is_fake_online', true)
            ->get(['id', 'app']);

        foreach ($activeFakeGreens as $master) {
            $this->appointmentRedisService->setAvailableFlag(
                $master->id,
                0,
                null,
                $master->app ?? null
            );
        }
    }

    private function getSettingValue(string $key): mixed
    {
        return AppSetting::query()
            ->where('key', $key)
            ->first()
            ?->value;
    }

    private function getSettingValueForApp(string $baseKey, string $app): mixed
    {
        $scoped = $this->getSettingValue($this->getAppSpecificKey($baseKey, $app));

        if ($scoped !== null) {
            return $scoped;
        }

        return $this->getSettingValue($baseKey);
    }

    private function getAppSpecificKey(string $baseKey, string $app): string
    {
        return sprintf('%s_%s', $baseKey, $app);
    }

    private function resolveCurrentApp(): string
    {
        $brand = config('app.client');

        return $brand instanceof AppBrand
            ? $brand->value
            : (string) ($brand ?: AppBrand::CARBEAT->value);
    }

    private function extractIntervalsForDay(array $workingHours, CarbonInterface $at): array
    {
        $dayKeys = [
            strtolower($at->englishDayOfWeek),
            strtolower(substr($at->englishDayOfWeek, 0, 3)),
        ];

        foreach ($dayKeys as $dayKey) {
            if (! array_key_exists($dayKey, $workingHours)) {
                continue;
            }

            return $this->normalizeIntervals($workingHours[$dayKey]);
        }

        return [];
    }

    private function normalizeIntervals(mixed $value): array
    {
        if (! is_array($value) || $value === []) {
            return [];
        }

        if (isset($value['from'], $value['to'])) {
            return [[
                'start' => (string) $value['from'],
                'end' => (string) $value['to'],
            ]];
        }

        if (isset($value['open'], $value['close'])) {
            return [[
                'start' => (string) $value['open'],
                'end' => (string) $value['close'],
            ]];
        }

        $intervals = [];
        foreach ($value as $interval) {
            if (! is_array($interval)) {
                continue;
            }

            $start = $interval['from'] ?? $interval['open'] ?? null;
            $end = $interval['to'] ?? $interval['close'] ?? null;

            if ($start && $end) {
                $intervals[] = [
                    'start' => (string) $start,
                    'end' => (string) $end,
                ];
            }
        }

        return $intervals;
    }

    private function isCurrentTimeInInterval(CarbonInterface $at, string $start, string $end): bool
    {
        try {
            $startAt = Carbon::parse($at->toDateString() . ' ' . $start, $at->getTimezone());
            $endAt = Carbon::parse($at->toDateString() . ' ' . $end, $at->getTimezone());
        } catch (\Throwable) {
            return false;
        }

        if ($endAt->lessThanOrEqualTo($startAt)) {
            $endAt->addDay();
        }

        return $at->betweenIncluded($startAt, $endAt);
    }
}
