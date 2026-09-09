<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\VisitEvent;
use App\Models\VisitSession;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class VisitMonitoringController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Visits/Index');
    }

    public function list(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->get('per_page', 20), 1), 100);

        $query = VisitSession::query()->orderByDesc('last_seen_at');

        if ($request->filled('ip')) {
            $query->where('ip_address', 'like', '%'.$request->get('ip').'%');
        }

        if ($request->filled('referrer_host')) {
            $query->where('referrer_host', 'like', '%'.$request->get('referrer_host').'%');
        }

        if ($request->filled('device_type')) {
            $query->where('device_type', $request->get('device_type'));
        }

        if ($request->filled('date_from')) {
            $query->where('last_seen_at', '>=', $request->get('date_from'));
        }

        if ($request->filled('date_to')) {
            $query->where('last_seen_at', '<=', $request->get('date_to'));
        }

        /** @var LengthAwarePaginator $items */
        $items = $query->paginate($perPage);

        return response()->json($items);
    }

    public function show(VisitSession $visitSession): JsonResponse
    {
        return response()->json([
            'session' => $visitSession,
            'events' => $visitSession->events()->orderByDesc('created_at')->get(),
        ]);
    }

    public function stats(): JsonResponse
    {
        $since = now()->subDays(30);

        $topReferrers = VisitSession::query()
            ->whereNotNull('referrer_host')
            ->where('started_at', '>=', $since)
            ->select('referrer_host', DB::raw('COUNT(*) as c'))
            ->groupBy('referrer_host')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $topPages = VisitEvent::query()
            ->where('type', VisitEvent::TYPE_PAGEVIEW)
            ->where('created_at', '>=', $since)
            ->whereNotNull('path')
            ->select('path', DB::raw('COUNT(*) as c'))
            ->groupBy('path')
            ->orderByDesc('c')
            ->limit(10)
            ->get();

        $byDevice = VisitSession::query()
            ->where('started_at', '>=', $since)
            ->select('device_type', DB::raw('COUNT(*) as c'))
            ->groupBy('device_type')
            ->pluck('c', 'device_type');

        // Averaged in PHP rather than SQL so it doesn't depend on a
        // database-specific date-diff function (sqlite in tests, MySQL in
        // production).
        $avgDurationSeconds = (int) round(
            VisitSession::where('started_at', '>=', $since)
                ->get(['started_at', 'last_seen_at'])
                ->avg(fn (VisitSession $session) => $session->duration_seconds) ?? 0
        );

        return response()->json([
            'total_sessions' => VisitSession::where('started_at', '>=', $since)->count(),
            'total_pageviews' => VisitEvent::where('type', VisitEvent::TYPE_PAGEVIEW)->where('created_at', '>=', $since)->count(),
            'total_clicks' => VisitEvent::where('type', VisitEvent::TYPE_CLICK)->where('created_at', '>=', $since)->count(),
            'sessions_today' => VisitSession::whereDate('started_at', now()->toDateString())->count(),
            'avg_duration_seconds' => $avgDurationSeconds,
            'top_referrers' => $topReferrers,
            'top_pages' => $topPages,
            'by_device' => $byDevice,
        ]);
    }

    /**
     * Delete every visit session (and, via the FK cascade, its events) for
     * the current brand. Used to clear out data recorded before bot
     * filtering (BotDetector) or the IP/duplicate-pageview fixes landed.
     */
    public function clear(): JsonResponse
    {
        $deleted = VisitSession::query()->delete();

        return response()->json([
            'success' => true,
            'deleted' => $deleted,
        ]);
    }
}
