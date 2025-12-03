<?php

use App\Http\Controllers\ClaimLinkController;
use App\Http\Controllers\PublicMasterController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/m/{slug}', [PublicMasterController::class, 'show'])->name('public.master.show');
Route::get('/claim/{token}', ClaimLinkController::class)->name('claim.redirect');

Route::redirect('/', '/admin');

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
