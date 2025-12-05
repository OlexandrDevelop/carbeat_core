<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminAppConfigUpdateRequest;
use App\Http\Requests\Admin\AdminSubscriptionConfigUpdateRequest;
use App\Http\Resources\Admin\AdminAppConfigResource;
use App\Http\Resources\Admin\AdminSubscriptionConfigResource;
use App\Http\Services\Admin\AppConfigService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class AppConfigController extends Controller
{
    public function __construct(private readonly AppConfigService $service)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/AppConfig/Index');
    }

    public function getVersions(): JsonResponse
    {
        return response()->json(new AdminAppConfigResource($this->service->getVersions()));
    }

    public function updateVersions(AdminAppConfigUpdateRequest $request): JsonResponse
    {
        $data = $this->service->updateVersions($request->validated());
        return response()->json(new AdminAppConfigResource($data));
    }

    public function getSubscription(): JsonResponse
    {
        return response()->json(new AdminSubscriptionConfigResource($this->service->getSubscription()));
    }

    public function updateSubscription(AdminSubscriptionConfigUpdateRequest $request): JsonResponse
    {
        $data = $this->service->updateSubscription($request->validated());
        return response()->json(new AdminSubscriptionConfigResource($data));
    }
}


