<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Services\MobileActivityService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

class MobileActivityController extends Controller
{
    public function __construct(
        private readonly MobileActivityService $activityService
    ) {}

    /**
     * Display the mobile activity monitoring page
     */
    public function index(): Response
    {
        return Inertia::render('Admin/MobileActivity/Index');
    }

    /**
     * Get current activity data (API endpoint for real-time updates)
     */
    public function getData(): JsonResponse
    {
        $users = $this->activityService->getActiveUsers();
        $stats = $this->activityService->getStats();

        return response()->json([
            'users' => $users,
            'stats' => $stats,
            'timestamp' => now()->timestamp,
        ]);
    }

    /**
     * Clear all activity data
     */
    public function clear(): JsonResponse
    {
        $this->activityService->clearAll();

        return response()->json([
            'success' => true,
            'message' => 'Activity data cleared successfully',
        ]);
    }
}

