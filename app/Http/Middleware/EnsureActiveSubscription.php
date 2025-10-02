<?php

namespace App\Http\Middleware;

use App\Http\Services\SubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveSubscription
{
    public function __construct(private readonly SubscriptionService $subscriptionService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        $this->subscriptionService->assertUserHasActiveSubscription($user->id);

        return $next($request);
    }
}
