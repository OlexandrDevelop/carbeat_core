<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Services\MasterCrmService;
use App\Models\Master;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MasterCrmController extends Controller
{
    public function __construct(private readonly MasterCrmService $crmService)
    {
    }

    public function snapshot(Request $request): JsonResponse
    {
        $master = $this->resolveAuthenticatedMaster($request);
        if (! $master) {
            return response()->json(['message' => 'Master profile not found.'], 404);
        }

        $businessDay = $request->query('date', now()->toDateString());

        $snapshot = $this->crmService->buildSnapshot($master, $businessDay);

        return response()->json($snapshot);
    }

    public function sync(Request $request): JsonResponse
    {
        $master = $this->resolveAuthenticatedMaster($request);
        if (! $master) {
            return response()->json(['message' => 'Master profile not found.'], 404);
        }

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

    private function resolveAuthenticatedMaster(Request $request): ?Master
    {
        $user = $request->user();
        if (! $user) {
            return null;
        }
        return Master::where('user_id', $user->id)->first();
    }
}
