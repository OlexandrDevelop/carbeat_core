<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Claim\ClaimSendSmsRequest;
use App\Http\Requests\Claim\ClaimVerifyRequest;
use App\Http\Services\ClaimService;
use Illuminate\Http\JsonResponse;

class ClaimController extends Controller
{
    public function __construct(
        private readonly ClaimService $claimService
    ) {}

    public function publicInfo(string $token): JsonResponse
    {
        $data = $this->claimService->getPublicInfo($token);

        return response()->json($data);
    }

    public function sendSms(ClaimSendSmsRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->claimService->sendSms($data['master_id'], $data['phone']);

            return response()->json($result);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 400;

            return response()->json([
                'status' => 'already_claimed',
                'message' => $e->getMessage(),
            ], $statusCode);
        }
    }

    public function verify(ClaimVerifyRequest $request): JsonResponse
    {
        $data = $request->validated();

        try {
            $result = $this->claimService->verify(
                $data['master_id'],
                $data['phone'],
                $data['code']
            );

            return response()->json($result);
        } catch (\Exception $e) {
            $statusCode = $e->getCode() >= 400 && $e->getCode() < 600 ? $e->getCode() : 422;

            return response()->json(['error' => $e->getMessage()], $statusCode);
        }
    }
}

