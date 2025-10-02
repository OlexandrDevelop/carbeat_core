<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminPaymentSettingsUpdateRequest;
use App\Http\Resources\Admin\AdminPaymentSettingsResource;
use App\Http\Services\Admin\PaymentSettingsService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentSettingsController extends Controller
{
    public function __construct(private readonly PaymentSettingsService $service)
    {
    }

    // Inertia page
    public function index(): Response
    {
        return Inertia::render('Admin/Payments/Index');
    }

    // Admin API
    public function get(): JsonResponse
    {
        $settings = $this->service->get();
        return response()->json(new AdminPaymentSettingsResource($settings));
    }

    public function update(AdminPaymentSettingsUpdateRequest $request): JsonResponse
    {
        $settings = $this->service->update($request->validated());
        return response()->json(new AdminPaymentSettingsResource($settings));
    }
}
