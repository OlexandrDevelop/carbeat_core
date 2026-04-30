<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Services\SmartRandomStatusService;
use App\Models\Master;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SmartRandomStatusServiceTest extends TestCase
{
    public function test_uses_global_window_when_working_hours_are_missing(): void
    {
        $service = new SmartRandomStatusService;
        $master = new Master([
            'working_hours' => null,
        ]);

        $this->assertTrue($service->isWithinActiveWindow($master, Carbon::parse('2026-04-30 10:00:00')));
        $this->assertFalse($service->isWithinActiveWindow($master, Carbon::parse('2026-04-30 21:00:00')));
    }

    public function test_supports_interval_lists_in_working_hours(): void
    {
        $service = new SmartRandomStatusService;
        $master = new Master([
            'working_hours' => [
                'thursday' => [
                    ['open' => '09:00', 'close' => '18:00'],
                ],
            ],
        ]);

        $this->assertTrue($service->isWithinActiveWindow($master, Carbon::parse('2026-04-30 10:00:00')));
        $this->assertFalse($service->isWithinActiveWindow($master, Carbon::parse('2026-04-30 20:00:00')));
    }
}
