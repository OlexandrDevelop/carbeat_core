<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminServiceDeleteRequest;
use App\Http\Requests\Admin\AdminServiceMergeRequest;
use App\Http\Requests\Admin\AdminServiceUpdateProvidersRequest;
use App\Http\Requests\Admin\AdminServiceUpdateRequest;
use App\Http\Resources\Admin\AdminServiceDeletePreviewResource;
use App\Http\Resources\Admin\AdminServiceListResource;
use App\Http\Resources\Admin\AdminServiceDeleteResponse;
use App\Http\Resources\Admin\AdminServiceResource;
use App\Http\Services\Admin\ServiceAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceAdminService $service)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Services/Index');
    }

    public function edit(int $id): Response
    {
        return Inertia::render('Admin/Services/Edit', [
            'serviceId' => $id,
        ]);
    }

    public function list(Request $request): JsonResponse
    {
        $result = $this->service->list($request->all());
        return response()->json(new AdminServiceListResource($result));
    }

    public function get(int $id): JsonResponse
    {
        $data = $this->service->get($id);
        return response()->json(new AdminServiceResource($data));
    }

    public function update(int $id, AdminServiceUpdateRequest $request): JsonResponse
    {
        $data = $this->service->update($id, $request->validated());
        return response()->json(new AdminServiceResource($data));
    }

    public function updateProviders(int $id, AdminServiceUpdateProvidersRequest $request): JsonResponse
    {
        $data = $this->service->updateProviders($id, $request->validated());
        return response()->json(new AdminServiceResource($data));
    }

    public function deletePreview(int $id): JsonResponse
    {
        $preview = $this->service->getDeletePreview($id);
        return response()->json(new AdminServiceDeletePreviewResource($preview));
    }

    public function destroy(int $id, AdminServiceDeleteRequest $request): JsonResponse
    {
        $summary = $this->service->deleteServiceAndCascade($id);
        return response()->json(new AdminServiceDeleteResponse($summary));
    }

    public function bulkDeletePreview(Request $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $preview = $this->service->getBulkDeletePreview($ids);
        return response()->json(new AdminServiceDeletePreviewResource($preview));
    }

    public function bulkDestroy(AdminServiceDeleteRequest $request): JsonResponse
    {
        $ids = $request->input('ids', []);
        $summary = $this->service->deleteServicesAndCascade($ids);
        return response()->json(new AdminServiceDeleteResponse($summary));
    }

    public function merge(AdminServiceMergeRequest $request): JsonResponse
    {
        $serviceIds = $request->input('service_ids', []);
        $primaryId = (int) $request->input('primary_id');
        $result = $this->service->merge($serviceIds, $primaryId);
        return response()->json($result);
    }
}
