<?php

use App\Http\Controllers\Master\AuthController;
use App\Http\Controllers\Master\CrmController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Master OTP auth routes (session login) — fully separate from /admin-auth.
Route::group(['prefix' => 'master-auth'], function () {
    Route::post('/request-otp', [AuthController::class, 'requestOtp'])->name('master.auth.request_otp');
    Route::post('/verify-otp', [AuthController::class, 'verifyOtp'])->name('master.auth.verify_otp');
});

// Master UI routes (Inertia pages)
Route::group(['prefix' => 'master', 'middleware' => ['auth', 'master.access']], function () {
    Route::get('/', function () {
        return redirect()->route('master.schedule.index');
    })->name('master.dashboard');

    Route::get('/schedule', function () {
        return Inertia::render('Master/Schedule/Index');
    })->name('master.schedule.index');

    Route::get('/catalog', function () {
        return Inertia::render('Master/Catalog/Index');
    })->name('master.catalog.index');

    Route::get('/clients', function () {
        return Inertia::render('Master/Clients/Index');
    })->name('master.clients.index');

    Route::get('/appointments', function () {
        return Inertia::render('Master/Appointments/Index');
    })->name('master.appointments.index');

    Route::get('/finance', function () {
        return Inertia::render('Master/Finance/Index');
    })->name('master.finance.index');

    Route::get('/settings', function () {
        return Inertia::render('Master/Settings/Index');
    })->name('master.settings.index');
});

// Master JSON API routes (session-authenticated, `api` group only formats
// the response — this is not JWT, see App\Http\Middleware\EnsureIsMaster).
Route::group(['prefix' => 'master-api', 'middleware' => ['auth', 'master.access', 'api']], function () {
    Route::get('/crm/snapshot', [CrmController::class, 'snapshot'])->name('master.api.crm.snapshot');
    Route::post('/crm/sync', [CrmController::class, 'sync'])->name('master.api.crm.sync');
    Route::get('/crm/finance', [CrmController::class, 'finance'])->name('master.api.crm.finance');
    Route::get('/crm/appointments', [CrmController::class, 'appointments'])->name('master.api.crm.appointments');
});
