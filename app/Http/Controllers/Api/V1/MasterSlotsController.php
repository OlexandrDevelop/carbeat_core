<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\Master\MasterScheduleService;
use App\Http\Services\ScheduleService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterSlotsController extends Controller
{
    public function __construct(
        private readonly MasterScheduleService $scheduleService,
        private readonly ScheduleService $schedule
    ) {}

    public function listDay(int $id, Request $request): JsonResponse
    {
        $date = Carbon::parse($request->query('date', now()->toDateString()));
        $intervals = $this->schedule->computeDayIntervals($id, $date);

        return response()->json([
            'date' => $date->toDateString(),
            'intervals' => $intervals,
        ]);
    }

    public function addRule(int $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'active' => ['sometimes', 'boolean'],
        ]);

        $rule = $this->scheduleService->createWorkScheduleRule($id, $data);

        return response()->json($rule);
    }

    public function deleteRule(int $id, int $ruleId): JsonResponse
    {
        $this->scheduleService->deleteWorkScheduleRule($id, $ruleId);

        return response()->json(['status' => 'ok']);
    }

    public function addTimeOff(int $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $off = $this->scheduleService->createTimeOff($id, $data);

        return response()->json($off);
    }

    public function deleteTimeOff(int $id, int $offId): JsonResponse
    {
        $this->scheduleService->deleteTimeOff($id, $offId);

        return response()->json(['status' => 'ok']);
    }

    public function syncDay(int $id, Request $request): JsonResponse
    {
        $date = Carbon::parse($request->query('date', now()->toDateString()));
        $this->schedule->syncDayToRedis($id, $date);

        return response()->json(['status' => 'ok']);
    }
}
