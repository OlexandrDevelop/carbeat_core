<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\AdminMaintenanceRegenerateThumbsRequest;
use App\Http\Requests\Admin\AdminMaintenanceTruncateRequest;
use App\Http\Resources\Admin\AdminMaintenanceRegenerateThumbsResource;
use App\Http\Resources\Admin\AdminMaintenanceTruncateResource;
use App\Http\Services\Admin\GalleryMaintenanceService;
use App\Http\Services\Admin\SystemMaintenanceService;
use Illuminate\Http\JsonResponse;

class MaintenanceController extends Controller
{
    public function __construct(
        private readonly GalleryMaintenanceService $galleryService,
        private readonly SystemMaintenanceService $systemService
    ) {
    }

    public function cleanupMissingGallery(): JsonResponse
    {
        $result = $this->galleryService->cleanupMissingFiles();
        return response()->json($result);
    }

    public function truncate(AdminMaintenanceTruncateRequest $request): JsonResponse
    {
        $result = $this->systemService->truncateDomainTables();
        return (new AdminMaintenanceTruncateResource($result))->response();
    }

    public function regenerateThumbs(AdminMaintenanceRegenerateThumbsRequest $request): JsonResponse
    {
        $reset = (bool) ($request->validated()['reset'] ?? true);
        $result = $this->systemService->regenerateAllThumbnails($reset);
        return (new AdminMaintenanceRegenerateThumbsResource($result))->response();
    }
}
