<?php

namespace App\Http\Middleware;

use App\Models\Master;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mirrors EnsureAdminAccess for the master portal: resolves the Master row
 * tied to the authenticated (session) user for the current brand and aborts
 * with 403 if none exists. The resolved Master is attached to the request so
 * downstream controllers (App\Http\Controllers\Master\*) don't re-query it.
 */
class EnsureIsMaster
{
    public function handle(Request $request, Closure $next): Response
    {
        $master = Master::where('user_id', $request->user()?->id)->first();

        abort_unless($master !== null, 403);

        $request->attributes->set('master', $master);

        return $next($request);
    }
}
