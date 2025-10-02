<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\ScheduleService;
use App\Models\MasterWorkSchedule;
use App\Models\MasterTimeOff;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterSlotsController extends Controller
{
    public function listDay(int $id, Request $request, ScheduleService $schedule): JsonResponse
    {
        $date = Carbon::parse($request->query('date', now()->toDateString()));
        $intervals = $schedule->computeDayIntervals($id, $date);
        return response()->json(['date' => $date->toDateString(), 'intervals' => $intervals]);
    }

    public function addRule(int $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'active' => ['sometimes', 'boolean'],
        ]);
        $data['master_id'] = $id;
        $rule = MasterWorkSchedule::create($data);
        return response()->json($rule);
    }

    public function deleteRule(int $id, int $ruleId): JsonResponse
    {
        MasterWorkSchedule::where('master_id', $id)->where('id', $ruleId)->delete();
        return response()->json(['status' => 'ok']);
    }

    public function addTimeOff(int $id, Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_time' => ['required', 'date'],
            'end_time' => ['required', 'date', 'after:start_time'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);
        $data['master_id'] = $id;
        $off = MasterTimeOff::create($data);
        return response()->json($off);
    }

    public function deleteTimeOff(int $id, int $offId): JsonResponse
    {
        MasterTimeOff::where('master_id', $id)->where('id', $offId)->delete();
        return response()->json(['status' => 'ok']);
    }

    public function syncDay(int $id, Request $request, ScheduleService $schedule): JsonResponse
    {
        $date = Carbon::parse($request->query('date', now()->toDateString()));
        $schedule->syncDayToRedis($id, $date);
        return response()->json(['status' => 'ok']);
    }
}
