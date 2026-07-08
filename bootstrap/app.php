<?php

use App\Console\Commands\GenerateSlugForMasters;
use App\Console\Commands\SyncSmartRandomStatuses;
use App\Console\Commands\SyncSubscriptions;
use App\Http\Middleware\AddSecurityHeaders;
use App\Http\Middleware\AdminBrand;
use App\Http\Middleware\DetectApp;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\TrackMobileActivity;
use App\Http\Services\TelegramService;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Middleware\HandleCors;
use Illuminate\Support\Facades\Log;
use Sentry\Laravel\Integration;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api_v1.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(HandleCors::class);
        $middleware->append(AddSecurityHeaders::class);
        $middleware->web(prepend: [
            DetectApp::class,
        ]);
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetLocale::class,
        ]);

        // Ensure CSRF protection is enabled for web routes
        $middleware->web(append: [
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);
        $middleware->api(prepend: [
            DetectApp::class,
        ]);
        $middleware->api(append: [
            SetLocale::class,
            TrackMobileActivity::class,
        ]);

        // Register route middleware aliases
        $middleware->alias([
            'active.subscription' => \App\Http\Middleware\EnsureActiveSubscription::class,
            'plan.feature' => \App\Http\Middleware\EnsurePlanAllowsFeature::class,
            'admin.brand' => AdminBrand::class,
            'admin.access' => \App\Http\Middleware\EnsureAdminAccess::class,
            'master.access' => \App\Http\Middleware\EnsureIsMaster::class,
        ]);

        // By default `auth` middleware redirects every unauthenticated web
        // request to the `login` route (the admin login). Master portal
        // routes must never bounce a master to the admin login, so redirect
        // based on which segment was hit — keeps admin/master auth fully
        // separate even for this framework-level redirect.
        \Illuminate\Auth\Middleware\Authenticate::redirectUsing(function ($request) {
            return $request->is('master', 'master/*')
                ? route('master-login')
                : route('login');
        });
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (\Throwable $e) {
            if (app()->bound(TelegramService::class) && app()->environment('local')) {
                app(TelegramService::class)->report($e);
            }
        });

        // AuthenticationException → 401 for API (must be before the catch-all \Throwable handler)
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });

        // ModelNotFoundException → clean 404 for API (no internal model class name exposed)
        $exceptions->render(function (\Illuminate\Database\Eloquent\ModelNotFoundException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                return response()->json(['error' => 'not_found'], 404);
            }
        });

        // HTTP exceptions (validation, auth, etc.) → structured JSON for API
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\HttpExceptionInterface $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                $message = $e->getMessage();
                // Avoid exposing empty or default framework messages
                if (empty($message)) {
                    $message = match ($e->getStatusCode()) {
                        401 => 'Unauthorized',
                        403 => 'Forbidden',
                        404 => 'Not found',
                        422 => 'Unprocessable content',
                        429 => 'Too many requests',
                        default => 'Server error',
                    };
                }

                return response()->json(['message' => $message], $e->getStatusCode());
            }

            $brand = config('app.client') instanceof \App\Enums\AppBrand
                ? config('app.client')
                : \App\Enums\AppBrand::CARBEAT;
            $errorPage = $brand === \App\Enums\AppBrand::FLOXCITY ? 'Floxcity/Error' : 'Carbeat/Error';

            return \Inertia\Inertia::render($errorPage, ['status' => $e->getStatusCode()])
                ->toResponse($request)
                ->setStatusCode($e->getStatusCode());
        });

        // Catch-all: any unhandled Throwable on API routes → generic 500, never expose stack traces
        $exceptions->render(function (\Throwable $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*')) {
                Log::error('Unhandled API exception', [
                    'exception' => get_class($e),
                    'message' => $e->getMessage(),
                    'url' => $request->fullUrl(),
                ]);

                return response()->json(['error' => 'server_error'], 500);
            }
        });

        Integration::handles($exceptions);
    })
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('sitemap:generate')
            ->daily()
            ->at('00:00')
            ->onSuccess(fn () => Log::info('Sitemap generated successfully'))
            ->onFailure(fn () => Log::error('Failed to generate sitemap'));

        $schedule->command('sitemap:generate-clean')
            ->daily()
            ->at('00:10')
            ->onSuccess(fn () => Log::info('Clean sitemap generated successfully'))
            ->onFailure(fn () => Log::error('Failed to generate clean sitemap'));

        // Safety net alongside the observer-driven `RefreshSeoOverridesJob`: catches
        // anything that changes masters/services/cities via a path the observers can't
        // see (e.g. raw queries in one-off maintenance commands like `services:normalize`).
        $schedule->command('seo:refresh')
            ->daily()
            ->at('00:20')
            ->onSuccess(fn () => Log::info('SEO overrides refreshed successfully'))
            ->onFailure(fn () => Log::error('Failed to refresh SEO overrides'));

        $schedule->command('subscriptions:sync')
            ->twiceDaily(0, 12)
            ->runInBackground();
        $schedule->command('telescope:prune --hours=48')
            ->daily()
            ->at('00:00');
        $schedule->command('masters:generate-thumbnails')
            ->everyFifteenMinutes();
        $schedule->command('smart-random-statuses:sync')
            ->everyFifteenMinutes()
            ->runInBackground();
    })
    ->withCommands(
        [
            GenerateSlugForMasters::class,
            SyncSmartRandomStatuses::class,
            \App\Console\Commands\ImportRatelist::class,
            \App\Commands\GenerateSitemap::class,
            SyncSubscriptions::class,
            \App\Console\Commands\NormalizeServiceNames::class,
            \App\Console\Commands\RefreshSeoContent::class,
        ]
    )
    ->create();
