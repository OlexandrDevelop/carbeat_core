<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\Admin\GalleryMaintenanceService;
use Illuminate\Http\JsonResponse;

class MaintenanceController extends Controller
{
    public function __construct(private readonly GalleryMaintenanceService $service)
    {
    }

    public function cleanupMissingGallery(): JsonResponse
    {
        $result = $this->service->cleanupMissingFiles();
        return response()->json($result);
    }
}
