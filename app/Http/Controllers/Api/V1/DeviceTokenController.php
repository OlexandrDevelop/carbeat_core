<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RegisterDeviceTokenRequest;
use App\Http\Services\DeviceTokenService;
use Illuminate\Http\JsonResponse;

class DeviceTokenController extends Controller
{
    public function store(RegisterDeviceTokenRequest $request, DeviceTokenService $deviceTokenService): JsonResponse
    {
        $deviceTokenService->register(auth('api')->user(), $request->validated());

        return response()->json(['status' => 'ok']);
    }
}
