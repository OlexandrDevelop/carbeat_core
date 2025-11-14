<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Subscription;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class SubscriptionsAdminController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Admin/Subscriptions/Index');
    }

    public function list(): JsonResponse
    {
        $perPage = min(max((int) request('per_page', 20), 1), 100);
        /** @var LengthAwarePaginator $items */
        $items = Subscription::query()
            ->with(['user:id,name,phone', 'user.master:id,user_id,name'])
            ->orderByDesc('created_at')
            ->paginate($perPage);

        return response()->json($items);
    }

    public function stats(): JsonResponse
    {
        $total = Subscription::count();
        $active = Subscription::where('status', 'active')
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            })->count();
        $expired = Subscription::where('status', 'expired')->orWhere('expires_at', '<=', now())->count();
        $byPlatform = Subscription::select('platform', DB::raw('COUNT(*) as c'))
            ->groupBy('platform')
            ->pluck('c', 'platform');
        $byProduct = Subscription::select('product_id', DB::raw('COUNT(*) as c'))
            ->groupBy('product_id')
            ->orderByDesc('c')
            ->limit(20)
            ->get();

        return response()->json([
            'total' => $total,
            'active' => $active,
            'expired' => $expired,
            'by_platform' => $byPlatform,
            'top_products' => $byProduct,
        ]);
    }
}


