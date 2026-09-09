<?php

namespace App\Http\Controllers;

use App\Http\Services\Visit\VisitTrackingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitTrackingController extends Controller
{
    public function __construct(
        private readonly VisitTrackingService $trackingService
    ) {}

    /**
     * Record a single pageview or click event sent by useVisitTracking.ts.
     * Public, unauthenticated, throttled — see routes/web.php.
     */
    public function track(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_token' => ['required', 'uuid'],
            'type' => ['required', 'string', 'in:pageview,click'],
            'path' => ['nullable', 'string', 'max:2048'],
            'url' => ['nullable', 'string', 'max:2048'],
            'referrer' => ['nullable', 'string', 'max:2048'],
            'element' => ['nullable', 'array'],
            'element.tag' => ['nullable', 'string', 'max:20'],
            'element.text' => ['nullable', 'string'],
            'element.id' => ['nullable', 'string', 'max:255'],
            'element.class' => ['nullable', 'string'],
            'element.href' => ['nullable', 'string', 'max:2048'],
            'meta' => ['nullable', 'array'],
        ]);

        $this->trackingService->record($request, $data);

        return response()->json(['ok' => true]);
    }
}
