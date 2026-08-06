<?php

use App\Http\Controllers\RepairRequestController;
use Illuminate\Support\Facades\Route;

// Repair-request form (Carbeat brand only, enforced by the carbeat.only
// middleware — see App\Http\Middleware\EnsureCarbeatBrand).
Route::group(['middleware' => 'carbeat.only'], function () {
    Route::get('/repair-request', [RepairRequestController::class, 'create'])->name('repair-request.create');

    Route::get('/repair-request/cities', [RepairRequestController::class, 'citySuggestions'])
        ->middleware('throttle:20,1')
        ->name('repair-request.cities');

    Route::post('/repair-request/request-otp', [RepairRequestController::class, 'requestOtp'])
        ->middleware('throttle:6,1')
        ->name('repair-request.request_otp');

    Route::post('/repair-request/verify-otp', [RepairRequestController::class, 'verifyAndSubmit'])
        ->middleware('throttle:6,1')
        ->name('repair-request.verify_otp');
});
