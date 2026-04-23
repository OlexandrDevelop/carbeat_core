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
        // Priority: X-App header → APP_CLIENT env var → hostname → default CARBEAT
        if ($request->header('X-App')) {
            $brand = AppBrand::fromHeader($request->header('X-App'));
        } elseif (env('APP_CLIENT')) {
            $brand = AppBrand::fromHeader(env('APP_CLIENT'));
        } else {
            $brand = AppBrand::fromHost($request->getHost());
        }
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
