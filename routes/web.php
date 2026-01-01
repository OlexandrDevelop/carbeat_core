<?php

use App\Http\Controllers\ClaimLinkController;
use App\Http\Controllers\PublicMasterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/m/{slug}', [PublicMasterController::class, 'show'])->name('public.master.show');
Route::get('/claim/{token}', ClaimLinkController::class)->name('claim.redirect');

// Public pages
Route::get('/terms', fn () => Inertia::render('Terms'))->name('terms');
Route::get('/privacy', fn () => Inertia::render('Privacy'))->name('privacy');
Route::get('/data-deletion', fn () => Inertia::render('DataDeletion'))->name('data_deletion');

Route::get('/', function () {
    $structuredData = [
        '@context' => 'https://schema.org',
        '@type' => 'SoftwareApplication',
        'name' => 'Carbeat',
        'operatingSystem' => 'ANDROID',
        'applicationCategory' => 'BusinessApplication',
        'aggregateRating' => [
            '@type' => 'AggregateRating',
            'ratingValue' => '4.5',
            'ratingCount' => '123',
        ],
        'offers' => [
            '@type' => 'Offer',
            'price' => '0',
            'priceCurrency' => 'UAH',
        ],
    ];

    return Inertia::render('Landing', [
        'appName' => config('app.name'),
        'adminUrl' => route('login'),
        'termsUrl' => route('terms'),
        'privacyUrl' => route('privacy'),
        'dataDeletionUrl' => route('data_deletion'),
        'playMarketUrl' => 'https://play.google.com/store/apps/details?id=com.carbeat',
        'structuredData' => $structuredData,
    ]);
})->name('landing');

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
