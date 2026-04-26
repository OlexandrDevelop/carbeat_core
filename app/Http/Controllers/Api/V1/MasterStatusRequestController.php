<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\RespondMasterStatusRequest;
use App\Http\Requests\Api\V1\StoreMasterStatusRequestRequest;
use App\Http\Services\MasterStatusRequestService;
use App\Models\MasterStatusRequest;
use Illuminate\Http\JsonResponse;

class MasterStatusRequestController extends Controller
{
    public function store(StoreMasterStatusRequestRequest $request, MasterStatusRequestService $service): JsonResponse
    {
        $driver = auth('api')->user();
        $payload = $request->validated();

        if ($driver && ! empty($payload['driver_user_id']) && (int) $payload['driver_user_id'] !== (int) $driver->id) {
            return response()->json(['message' => 'driver_user_id mismatch'], 422);
        }

        return response()->json($service->createRequest($driver, $payload));
    }

    public function respond(
        RespondMasterStatusRequest $request,
        MasterStatusRequest $masterStatusRequest,
        MasterStatusRequestService $service
    ): JsonResponse {
        $user = auth('api')->user();

        if ((int) ($masterStatusRequest->master->user_id ?? 0) !== (int) $user->id) {
            return response()->json(['message' => 'forbidden'], 403);
        }

        return response()->json(
            $service->respond($masterStatusRequest, $request->validated()['answer'], 'app')
        );
    }
}
