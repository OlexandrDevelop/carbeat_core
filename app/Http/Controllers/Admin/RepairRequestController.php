<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RepairRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Read-only admin listing of driver-submitted repair requests (Carbeat-only
 * feature — see App\Http\Middleware\EnsureCarbeatBrand for the public side).
 * No edit/delete workflow is needed yet, just visibility into submissions.
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
                'created_at' => $repairRequest->created_at?->toIso8601String(),
            ]);

        return response()->json($requests);
    }
}
