<?php

namespace App\Http\Middleware;

use App\Models\Tariff;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePlanAllowsFeature
{
    public function handle(Request $request, Closure $next, string $featureKey): Response
    {
        $user = $request->user();
        if (! $user || ! $user->master) {
            abort(403);
        }

        $tariff = $user->master->tariff;
        $features = $tariff?->features ?? [];
        if (! in_array($featureKey, $features, true)) {
            abort(403, 'Feature not allowed for current plan');
        }

        return $next($request);
    }
}
