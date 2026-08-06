<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\PhoneHelper;
use App\Http\Controllers\Controller;
use App\Http\Services\RepairRequestNotificationService;
use App\Models\Master;
use App\Models\RepairRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Admin listing + manual moderation of driver-submitted repair requests
 * (Carbeat-only feature — see App\Http\Middleware\EnsureCarbeatBrand for the
 * public side). A request notifies no masters on its own
 * (App\Http\Controllers\RepairRequestController::verifyAndSubmit() only
 * alerts the ops Telegram chat) — approve() here is what actually triggers
 * App\Http\Services\RepairRequestNotificationService::notify().
 */
class RepairRequestController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/RepairRequests/Index');
    }

    public function list(Request $request): JsonResponse
    {
        $perPage = min((int) $request->integer('per_page', 20), 100);

        $requests = RepairRequest::query()
            ->with(['user:id,name,phone', 'service:id,name'])
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn (RepairRequest $repairRequest) => [
                'id' => $repairRequest->id,
                'name' => $repairRequest->name,
                'phone' => $repairRequest->phone,
                'car_make' => $repairRequest->car_make,
                'car_model' => $repairRequest->car_model,
                'car_year' => $repairRequest->car_year,
                'description' => $repairRequest->description,
                'service_name' => $repairRequest->service?->translate(app()->getLocale()),
                'city' => $repairRequest->city,
                'status' => $repairRequest->status,
                'created_at' => $repairRequest->created_at?->toIso8601String(),
            ]);

        return response()->json($requests);
    }

    public function approve(RepairRequest $repairRequest, RepairRequestNotificationService $notificationService): JsonResponse
    {
        if ($repairRequest->status === 'pending') {
            $repairRequest->forceFill([
                'status' => 'approved',
                'approved_at' => now(),
            ])->save();

            $notificationService->notify($repairRequest);
        }

        return response()->json(['status' => $repairRequest->status]);
    }

    public function reject(RepairRequest $repairRequest): JsonResponse
    {
        if ($repairRequest->status === 'pending') {
            $repairRequest->forceFill(['status' => 'rejected'])->save();
        }

        return response()->json(['status' => $repairRequest->status]);
    }

    /**
     * Which masters App\Http\Services\RepairRequestNotificationService
     * matched this request against (same service+50km-radius query it
     * actually notifies), plus how each one was/would be reached — lets an
     * admin see why a request did or didn't get a response.
     */
    public function matchedMasters(RepairRequest $repairRequest, RepairRequestNotificationService $notificationService): JsonResponse
    {
        $masters = $notificationService->matchingMasters($repairRequest)
            ->with('city:id,name')
            ->get();

        $data = $masters
            ->map(fn (Master $master) => [
                'id' => $master->id,
                'name' => $master->name,
                'city' => $master->city?->name,
                'distance_km' => $master->getAttribute('distance') !== null ? round((float) $master->getAttribute('distance'), 1) : null,
                'channel' => $this->notificationChannel($master),
                'sms_invite_count' => $master->repair_request_sms_invite_count,
            ])
            ->sortBy('distance_km')
            ->values();

        return response()->json(['data' => $data]);
    }

    private function notificationChannel(Master $master): string
    {
        if ($master->telegram_chat_id) {
            return 'telegram';
        }

        if (! PhoneHelper::isMobile((string) $master->contact_phone)) {
            return 'unreachable';
        }

        if ($master->repair_request_sms_invite_count >= RepairRequestNotificationService::SMS_INVITE_LIMIT) {
            return 'invite_limit_reached';
        }

        return 'sms';
    }
}
