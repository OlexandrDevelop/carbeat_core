<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\Subscription\CheckSubscriptionRequest;
use App\Http\Resources\Api\V1\SubscriptionStatusResource;
use App\Http\Services\SubscriptionService;
use Illuminate\Http\JsonResponse;
use Tymon\JWTAuth\Facades\JWTAuth;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionService $subscriptionService)
    {
    }

    /**
     * Verify subscription receipt/token from mobile app and store/update status.
     */
    public function check(CheckSubscriptionRequest $request): SubscriptionStatusResource
    {
        $data = $request->validated();
        $user = JWTAuth::user();

        $statusDto = $this->subscriptionService->verifyAndStore(
            $user->id,
            $data['platform'],
            $data['receipt_token'],
            $data['product_id'] ?? null
        );

        return new SubscriptionStatusResource($statusDto);
    }

    /**
     * Get current subscription status for the authenticated user.
     */
    public function status(): SubscriptionStatusResource
    {
        $user = JWTAuth::user();
        $statusDto = $this->subscriptionService->getStatus($user->id);
        return new SubscriptionStatusResource($statusDto);
    }
}
