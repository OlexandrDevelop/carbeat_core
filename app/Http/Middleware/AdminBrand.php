<?php

namespace App\Http\Middleware;

use App\Enums\AppBrand;
use Closure;
use Illuminate\Http\Request;

class AdminBrand
{
    public function handle(Request $request, Closure $next)
    {
        // 1) Try to read `brand` from query (switcher submits as GET)
        $brandParam = $request->query('brand');

        // 2) Fallbacks: session, cookie
        $stored = $request->session()->get('admin_brand');
        $cookieBrand = $request->cookie('admin_brand');

        $selected = null;
        try {
            if ($brandParam) {
                $selected = AppBrand::from($brandParam);
            }
        } catch (\Throwable $e) {
            $selected = null;
        }

        if (! $selected) {
            try {
                if ($stored) {
                    $selected = AppBrand::from($stored);
                } elseif ($cookieBrand) {
                    $selected = AppBrand::from($cookieBrand);
                }
            } catch (\Throwable $e) {
                $selected = null;
            }
        }

        // Default to CARBEAT if nothing selected
        $selected = $selected ?: AppBrand::CARBEAT;

        // Persist selection when explicit param provided
        if ($brandParam) {
            $request->session()->put('admin_brand', $selected->value);
            // also queue a cookie for admin-api calls that might not have session middleware
            cookie()->queue(cookie('admin_brand', $selected->value, 60 * 24 * 30)); // 30 days
        }

        // Set runtime brand for this request so AppScoped works automatically
        config(['app.client' => $selected]);

        return $next($request);
    }
}
