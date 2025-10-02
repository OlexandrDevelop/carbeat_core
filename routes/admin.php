<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Admin root redirect
Route::get('/admin', function () {
    return redirect()->route('admin.masters.index');
});

// Admin UI routes (Inertia pages)
Route::group(['prefix' => 'admin', 'middleware' => ['auth']], function () {
    Route::get('/masters', [\App\Http\Controllers\Admin\MasterController::class, 'index'])->name('admin.masters.index');
    Route::get('/masters/{id}/edit', [\App\Http\Controllers\Admin\MasterController::class, 'edit'])->name('admin.masters.edit');
    Route::get('/import', [\App\Http\Controllers\Admin\ImportController::class, 'index'])->name('admin.import.index');
Route::get('/api-docs', [\App\Http\Controllers\Admin\ApiDocsController::class, 'index'])->name('admin.api-docs.index');
    Route::get('/services', [\App\Http\Controllers\Admin\ServiceController::class, 'index'])->name('admin.services.index');
    Route::get('/services/{id}/edit', [\App\Http\Controllers\Admin\ServiceController::class, 'edit'])->name('admin.services.edit');

    // Subscriptions
    Route::get('/subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class, 'index'])->name('admin.subscriptions.index');
    Route::get('/subscriptions/{id}/edit', [\App\Http\Controllers\Admin\SubscriptionController::class, 'edit'])->name('admin.subscriptions.edit');

    // Payment settings
    Route::get('/payments', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'index'])->name('admin.payments.index');

    // Tariffs
    Route::get('/tariffs', [\App\Http\Controllers\Admin\TariffController::class, 'index'])->name('admin.tariffs.index');
    Route::get('/tariffs/{id}/edit', [\App\Http\Controllers\Admin\TariffController::class, 'edit'])->name('admin.tariffs.edit');
});

// Admin JSON API routes
Route::group(['prefix' => 'admin-api', 'middleware' => ['auth', 'api']], function () {
    Route::get('/masters', [\App\Http\Controllers\Admin\MasterController::class, 'list'])->name('admin.api.masters.list');
    Route::get('/masters/{id}', [\App\Http\Controllers\Admin\MasterController::class, 'get'])->name('admin.api.masters.get');
    Route::put('/masters/{id}', [\App\Http\Controllers\Admin\MasterController::class, 'update'])->name('admin.api.masters.update');
    Route::delete('/masters/{id}', [\App\Http\Controllers\Admin\MasterController::class, 'destroy'])->name('admin.api.masters.destroy');
    Route::delete('/masters', [\App\Http\Controllers\Admin\MasterController::class, 'destroyAll'])->name('admin.api.masters.destroy_all');
    Route::get('/services', [\App\Http\Controllers\Admin\MasterController::class, 'services'])->name('admin.api.services');
    Route::get('/masters/{id}/reviews', [\App\Http\Controllers\Admin\MasterController::class, 'reviews'])->name('admin.api.masters.reviews');
    Route::post('/masters/{id}/reviews', [\App\Http\Controllers\Admin\MasterController::class, 'storeReview'])->name('admin.api.masters.reviews.store');
    Route::put('/reviews/{reviewId}', [\App\Http\Controllers\Admin\MasterController::class, 'updateReview'])->name('admin.api.reviews.update');
    Route::delete('/reviews/{reviewId}', [\App\Http\Controllers\Admin\MasterController::class, 'deleteReview'])->name('admin.api.reviews.delete');

    // Users (needed by admin master edit page)
    Route::get('/users', [\App\Http\Controllers\Admin\UserController::class, 'index'])->name('admin.api.users.index');

    // Services management (admin)
    Route::get('/admin-services', [\App\Http\Controllers\Admin\ServiceController::class, 'list'])->name('admin.api.admin_services.list');
    Route::get('/admin-services/{id}', [\App\Http\Controllers\Admin\ServiceController::class, 'get'])->name('admin.api.admin_services.get');
    Route::put('/admin-services/{id}', [\App\Http\Controllers\Admin\ServiceController::class, 'update'])->name('admin.api.admin_services.update');
    Route::put('/admin-services/{id}/providers', [\App\Http\Controllers\Admin\ServiceController::class, 'updateProviders'])->name('admin.api.admin_services.update_providers');
    Route::get('/admin-services/{id}/delete-preview', [\App\Http\Controllers\Admin\ServiceController::class, 'deletePreview'])->name('admin.api.admin_services.delete_preview');
    Route::delete('/admin-services/{id}', [\App\Http\Controllers\Admin\ServiceController::class, 'destroy'])->name('admin.api.admin_services.destroy');
    Route::post('/admin-services/bulk/delete-preview', [\App\Http\Controllers\Admin\ServiceController::class, 'bulkDeletePreview'])->name('admin.api.admin_services.bulk_delete_preview');
    Route::post('/admin-services/bulk/delete', [\App\Http\Controllers\Admin\ServiceController::class, 'bulkDestroy'])->name('admin.api.admin_services.bulk_delete');

    // Subscriptions management (admin)
    Route::get('/subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class, 'list'])->name('admin.api.subscriptions.list');
    Route::get('/subscriptions/{id}', [\App\Http\Controllers\Admin\SubscriptionController::class, 'get'])->name('admin.api.subscriptions.get');
    Route::post('/subscriptions', [\App\Http\Controllers\Admin\SubscriptionController::class, 'create'])->name('admin.api.subscriptions.create');
    Route::put('/subscriptions/{id}', [\App\Http\Controllers\Admin\SubscriptionController::class, 'update'])->name('admin.api.subscriptions.update');
    Route::delete('/subscriptions/{id}', [\App\Http\Controllers\Admin\SubscriptionController::class, 'destroy'])->name('admin.api.subscriptions.destroy');
    Route::post('/subscriptions/bulk/delete', [\App\Http\Controllers\Admin\SubscriptionController::class, 'bulkDelete'])->name('admin.api.subscriptions.bulk_delete');
    Route::post('/subscriptions/bulk/status', [\App\Http\Controllers\Admin\SubscriptionController::class, 'bulkStatus'])->name('admin.api.subscriptions.bulk_status');
    Route::post('/subscriptions/verify', [\App\Http\Controllers\Admin\SubscriptionController::class, 'verify'])->name('admin.api.subscriptions.verify');
    Route::get('/subscriptions/export', [\App\Http\Controllers\Admin\SubscriptionController::class, 'export'])->name('admin.api.subscriptions.export');

    // Payments settings API
    Route::get('/payment-settings', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'get'])->name('admin.api.payments.get');
    Route::put('/payment-settings', [\App\Http\Controllers\Admin\PaymentSettingsController::class, 'update'])->name('admin.api.payments.update');

    // Tariffs API
    Route::get('/tariffs', [\App\Http\Controllers\Admin\TariffController::class, 'list'])->name('admin.api.tariffs.list');
    Route::get('/tariffs/{id}', [\App\Http\Controllers\Admin\TariffController::class, 'get'])->name('admin.api.tariffs.get');
    Route::post('/tariffs', [\App\Http\Controllers\Admin\TariffController::class, 'create'])->name('admin.api.tariffs.create');
    Route::put('/tariffs/{id}', [\App\Http\Controllers\Admin\TariffController::class, 'update'])->name('admin.api.tariffs.update');
    Route::delete('/tariffs/{id}', [\App\Http\Controllers\Admin\TariffController::class, 'destroy'])->name('admin.api.tariffs.destroy');
});

// Admin OTP auth routes (session login)
Route::group(['prefix' => 'admin-auth'], function () {
    Route::post('/request-otp', [\App\Http\Controllers\Admin\AuthController::class, 'requestOtp'])->name('admin.auth.request_otp');
    Route::post('/verify-otp', [\App\Http\Controllers\Admin\AuthController::class, 'verifyOtp'])->name('admin.auth.verify_otp');
});
