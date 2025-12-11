<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Enums\AppBrand;

class DetectApp
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        // Read brand from header via enum (defaults to CARBEAT)
        $brand = AppBrand::fromHeader($request->header('X-App'));
        config(['app.client' => $brand]);

        // Optionally load brand-specific config if present: config/brand/{brand}.php
        $brandConfigPath = config_path('brand/' . $brand->value . '.php');
        if (is_file($brandConfigPath)) {
            // This will merge the brand config into runtime config under 'brand'
            config(['brand' => include $brandConfigPath]);
        }

        return $next($request);
    }
}
