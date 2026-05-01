<?php

use App\Enums\AppBrand;
use App\Http\Controllers\ClaimLinkController;
use App\Http\Controllers\LandingController;
use App\Http\Controllers\MasterStatusRequestWebController;
use App\Http\Controllers\PublicGuestMapController;
use App\Http\Controllers\PublicMasterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/sitemap.xml', function () {
    $sitemapPath = public_path('sitemap.xml');

    if (!file_exists($sitemapPath)) {
        abort(404, 'Sitemap not found');
    }

    return response()->file($sitemapPath, [
        'Content-Type' => 'application/xml; charset=utf-8',
    ]);
})->name('sitemap');

Route::get('/robots.txt', function () {
    $content = implode("\n", [
        'User-agent: *',
        'Disallow:',
        'Sitemap: ' . route('sitemap'),
        '',
    ]);

    return response($content, 200, [
        'Content-Type' => 'text/plain; charset=utf-8',
    ]);
})->name('robots');

Route::get('/sto/{slug}', [PublicGuestMapController::class, 'showMaster'])->name('public.sto.show');
Route::get('/city/{citySlug}', [PublicGuestMapController::class, 'showCity'])->name('public.city.show');
Route::get('/city/{citySlug}/{serviceSlug}', [PublicGuestMapController::class, 'showCityService'])->name('public.city.service.show');
Route::get('/m/{slug}', [PublicMasterController::class, 'show'])->name('public.master.show');
Route::get('/claim/{token}', ClaimLinkController::class)->name('claim.redirect');
Route::get('/r/{token}', [MasterStatusRequestWebController::class, 'show'])->name('status-request.show');
Route::post('/r/{token}', [MasterStatusRequestWebController::class, 'respond'])->name('status-request.respond');

// Public pages — brand-specific
Route::get('/terms', function () {
    $brand = config('app.client') instanceof AppBrand ? config('app.client') : AppBrand::CARBEAT;
    return Inertia::render($brand === AppBrand::FLOXCITY ? 'Floxcity/Terms' : 'Carbeat/Terms');
})->name('terms');

Route::get('/privacy', function () {
    $brand = config('app.client') instanceof AppBrand ? config('app.client') : AppBrand::CARBEAT;
    return Inertia::render($brand === AppBrand::FLOXCITY ? 'Floxcity/Privacy' : 'Carbeat/Privacy');
})->name('privacy');

Route::get('/data-deletion', function () {
    $brand = config('app.client') instanceof AppBrand ? config('app.client') : AppBrand::CARBEAT;
    return Inertia::render($brand === AppBrand::FLOXCITY ? 'Floxcity/DataDeletion' : 'Carbeat/DataDeletion');
})->name('data_deletion');

Route::get('/', [PublicGuestMapController::class, 'index'])->name('landing');
Route::get('/guest-map', [PublicGuestMapController::class, 'index'])->name('public.guest-map');
Route::get('/landing', LandingController::class)->name('marketing.landing');

Route::middleware('guest')->group(function () {
    Route::get('/login', fn () => Inertia::render('Admin/Auth/Login'))->name('login');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', function (Request $request) {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    })->name('logout');
});

require __DIR__.'/admin.php';
