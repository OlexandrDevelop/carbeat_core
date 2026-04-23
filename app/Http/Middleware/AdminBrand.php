<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AdminBrand
{
    public function handle(Request $request, Closure $next)
    {
        // Brand is already set by DetectApp middleware based on hostname.
        // AppScoped uses config('app.client') automatically.
        return $next($request);
    }
}
