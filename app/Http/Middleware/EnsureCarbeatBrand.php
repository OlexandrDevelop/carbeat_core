<?php

namespace App\Http\Middleware;

use App\Enums\AppBrand;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hard brand gate for Carbeat-only features (e.g. the repair-request flow).
 * Unlike AdminBrand (a no-op marker), this actually rejects the request when
 * DetectApp resolved a non-Carbeat brand, mirroring the abort(404) idiom used
 * for the /sitemap-sto.xml route in routes/web.php.
 */
class EnsureCarbeatBrand
{
    public function handle(Request $request, Closure $next): Response
    {
        $brand = config('app.client') instanceof AppBrand
            ? config('app.client')
            : AppBrand::CARBEAT;

        abort_unless($brand === AppBrand::CARBEAT, 404);

        return $next($request);
    }
}
