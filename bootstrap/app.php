<?php

use App\Console\Commands\GenerateSlugForMasters;
use App\Console\Commands\SyncSubscriptions;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SetLocale;
use App\Http\Middleware\DetectApp;
use App\Http\Middleware\AdminBrand;
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
        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
            SetLocale::class,
        ]);

        // Ensure CSRF protection is enabled for web routes
        $middleware->web(append: [
            \Illuminate\Foundation\Http\Middleware\VerifyCsrfToken::class,
        ]);
        $middleware->api(append: [
            SetLocale::class,
            DetectApp::class,
        ]);

        // Register route middleware aliases
        $middleware->alias([
            'active.subscription' => \App\Http\Middleware\EnsureActiveSubscription::class,
            'plan.feature' => \App\Http\Middleware\EnsurePlanAllowsFeature::class,
            'admin.brand' => AdminBrand::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->reportable(function (\Throwable $e) {
            if (app()->bound(TelegramService::class) && app()->environment('local')) {
                app(TelegramService::class)->report($e);
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

        $schedule->command('subscriptions:sync')
            ->twiceDaily(0, 12)
            ->runInBackground();
    })
    ->withCommands(
        [
            GenerateSlugForMasters::class,
            \App\Console\Commands\ImportRatelist::class,
            \App\Commands\GenerateSitemap::class,
            SyncSubscriptions::class,
        ]
    )
    ->create();
