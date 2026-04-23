<?php

use App\Enums\AppBrand;
use App\Http\Controllers\ClaimLinkController;
use App\Http\Controllers\LandingController;
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

Route::get('/m/{slug}', [PublicMasterController::class, 'show'])->name('public.master.show');
Route::get('/claim/{token}', ClaimLinkController::class)->name('claim.redirect');

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

Route::get('/', LandingController::class)->name('landing');

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
