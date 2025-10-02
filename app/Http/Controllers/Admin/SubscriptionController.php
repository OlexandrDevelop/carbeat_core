<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminSubscriptionBulkDeleteRequest;
use App\Http\Requests\Admin\AdminSubscriptionBulkStatusRequest;
use App\Http\Requests\Admin\AdminSubscriptionCreateRequest;
use App\Http\Requests\Admin\AdminSubscriptionListRequest;
use App\Http\Requests\Admin\AdminSubscriptionUpdateRequest;
use App\Http\Requests\Admin\AdminSubscriptionVerifyRequest;
use App\Http\Resources\Admin\AdminSubscriptionResource;
use App\Http\Services\Admin\SubscriptionAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionController extends Controller
{
    public function __construct(private readonly SubscriptionAdminService $service)
    {
    }

    // Inertia pages
    public function index(): Response
    {
        return Inertia::render('Admin/Subscriptions/Index');
    }

    public function edit(int $id): Response
    {
        return Inertia::render('Admin/Subscriptions/Edit', [
            'subscriptionId' => $id,
        ]);
    }

    // Admin API
    public function list(AdminSubscriptionListRequest $request): JsonResponse
    {
        $paginator = $this->service->list($request->validated());
        return response()->json([
            'data' => AdminSubscriptionResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function get(int $id): JsonResponse
    {
        $sub = $this->service->get($id);
        return response()->json(new AdminSubscriptionResource($sub));
    }

    public function create(AdminSubscriptionCreateRequest $request): JsonResponse
    {
        $sub = $this->service->create($request->validated());
        return response()->json(new AdminSubscriptionResource($sub));
    }

    public function update(int $id, AdminSubscriptionUpdateRequest $request): JsonResponse
    {
        $sub = $this->service->update($id, $request->validated());
        return response()->json(new AdminSubscriptionResource($sub));
    }

    public function destroy(int $id): JsonResponse
    {
        $this->service->destroy($id);
        return response()->json(['status' => 'ok']);
    }

    public function bulkDelete(AdminSubscriptionBulkDeleteRequest $request): JsonResponse
    {
        $deleted = $this->service->bulkDelete($request->input('ids', []));
        return response()->json(['deleted' => $deleted]);
    }

    public function bulkStatus(AdminSubscriptionBulkStatusRequest $request): JsonResponse
    {
        $updated = $this->service->bulkStatus($request->input('ids', []), $request->input('status'));
        return response()->json(['updated' => $updated]);
    }

    public function verify(AdminSubscriptionVerifyRequest $request): JsonResponse
    {
        $status = $this->service->verify($request->validated());
        return response()->json($status);
    }

    public function export(AdminSubscriptionListRequest $request)
    {
        $csv = $this->service->exportCsv($request->validated());
        $filename = 'subscriptions_'.now()->format('Y_m_d_His').'.csv';
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }
}
