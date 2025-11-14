<?php

namespace App\Http\Middleware;

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

        $master = $user->master;
        if (in_array($featureKey, ['premium', 'premium_only'], true)) {
            $isPremiumActive = (bool) ($master->is_premium ?? false);
            if ($isPremiumActive && $master->premium_until) {
                $isPremiumActive = now()->lessThanOrEqualTo($master->premium_until);
            }
            if (! $isPremiumActive) {
                abort(403, 'Feature not allowed: premium required');
            }
        }

        return $next($request);
    }
}
