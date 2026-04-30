<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminSmartRandomStatusUpdateRequest;
use App\Http\Services\SmartRandomStatusService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class SmartRandomStatusController extends Controller
{
    public function __construct(private readonly SmartRandomStatusService $service)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/SmartRandomStatus/Index');
    }

    public function show(): JsonResponse
    {
        return response()->json($this->service->getDashboardData());
    }

    public function update(AdminSmartRandomStatusUpdateRequest $request): JsonResponse
    {
        $settings = $this->service->updateSettings($request->validated());
        $this->service->sync();

        return response()->json([
            'settings' => $settings,
            'stats' => $this->service->getStats(),
            'fake_green_masters' => $this->service->listFakeGreenMasters(),
        ]);
    }

    public function turnOff(int $masterId): JsonResponse
    {
        $master = $this->service->turnOffFakeStatus($masterId);

        if (! $master) {
            return response()->json(['message' => 'Fake green status not found'], 404);
        }

        return response()->json([
            'status' => 'ok',
            'stats' => $this->service->getStats(),
            'fake_green_masters' => $this->service->listFakeGreenMasters(),
        ]);
    }
}
