<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Http\Services\MasterCrmService;
use App\Http\Services\MasterFinanceService;
use App\Models\Master;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Web counterpart of Api\V1\MasterCrmController — same snapshot/sync
 * contract, reusing the unchanged MasterCrmService. The only difference is
 * how $master is resolved: here it comes pre-resolved from the session by
 * EnsureIsMaster (App\Http\Middleware\EnsureIsMaster), not re-queried from a
 * JWT-authenticated user.
 */
class CrmController extends Controller
{
    public function __construct(
        private readonly MasterCrmService $crmService,
        private readonly MasterFinanceService $financeService,
    ) {}

    public function snapshot(Request $request): JsonResponse
    {
        $master = $this->resolveMaster($request);

        $businessDay = $request->query('date', now()->toDateString());

        $snapshot = $this->crmService->buildSnapshot($master, $businessDay);

        return response()->json($snapshot);
    }

    public function sync(Request $request): JsonResponse
    {
        $master = $this->resolveMaster($request);

        $validated = $request->validate([
            'businessDay' => ['required', 'date_format:Y-m-d'],
            'changes' => ['nullable', 'array'],
            'changes.*.type' => ['required', 'string'],
            'changes.*.payload' => ['required', 'array'],
        ]);

        $changes = $validated['changes'] ?? [];

        $this->crmService->applyChanges($master, $changes, $validated['businessDay']);

        $snapshot = $this->crmService->buildSnapshot($master, $validated['businessDay']);

        return response()->json($snapshot);
    }

    public function appointments(Request $request): JsonResponse
    {
        $master = $this->resolveMaster($request);

        $validated = $request->validate([
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'bayId' => ['nullable', 'string'],
            'kind' => ['nullable', 'string', 'in:work,next,request'],
            'paymentStatus' => ['nullable', 'string', 'in:pending,partial,paid,debt'],
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
            'perPage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $result = $this->crmService->listAppointments($master, $validated);

        return response()->json($result);
    }

    public function finance(Request $request): JsonResponse
    {
        $master = $this->resolveMaster($request);

        $validated = $request->validate([
            'from' => ['required', 'date_format:Y-m-d'],
            'to' => ['required', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $report = $this->financeService->buildReport($master, $validated['from'], $validated['to']);

        return response()->json($report);
    }

    private function resolveMaster(Request $request): Master
    {
        /** @var Master $master */
        $master = $request->attributes->get('master');

        return $master;
    }
}
