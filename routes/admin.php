<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Admin\AppConfigController;
use App\Http\Controllers\Admin\SubscriptionsAdminController;

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

    // Tariffs removed (migrated to premium flags)

    // Maintenance tools (UI)
    Route::get('/maintenance', function () {
        return Inertia::render('Admin/Maintenance/Index');
    })->name('admin.maintenance.index');

    // Realtime monitoring: availability events dashboard
    Route::get('/realtime/availability', function () {
        return Inertia::render('Admin/Realtime/Availability');
    })->name('admin.realtime.availability');

    // App config UI
    Route::get('/app-config', [AppConfigController::class, 'index'])->name('admin.app_config.index');
    // Subscriptions dashboard UI
    Route::get('/subscriptions-dashboard', [SubscriptionsAdminController::class, 'index'])->name('admin.subscriptions.dashboard');
});

// Admin JSON API routes
Route::group(['prefix' => 'admin-api', 'middleware' => ['auth', 'api']], function () {
    Route::get('/masters', [\App\Http\Controllers\Admin\MasterController::class, 'list'])->name('admin.api.masters.list');
    Route::get('/masters/{id}', [\App\Http\Controllers\Admin\MasterController::class, 'get'])->name('admin.api.masters.get');
    Route::put('/masters/{id}', [\App\Http\Controllers\Admin\MasterController::class, 'update'])->name('admin.api.masters.update');
    Route::delete('/masters/{id}', [\App\Http\Controllers\Admin\MasterController::class, 'destroy'])->name('admin.api.masters.destroy');
    Route::delete('/masters', [\App\Http\Controllers\Admin\MasterController::class, 'destroyAll'])->name('admin.api.masters.destroy_all');
    Route::post('/masters/invite', [\App\Http\Controllers\Admin\MasterController::class, 'invite'])->name('admin.api.masters.invite');
    Route::get('/services', [\App\Http\Controllers\Admin\MasterController::class, 'services'])->name('admin.api.services');
    Route::get('/cities', [\App\Http\Controllers\Admin\MasterController::class, 'cities'])->name('admin.api.cities');
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
    Route::post('/admin-services/merge', [\App\Http\Controllers\Admin\ServiceController::class, 'merge'])->name('admin.api.admin_services.merge');
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

    // App config API
    Route::get('/app-config/versions', [AppConfigController::class, 'getVersions'])->name('admin.api.app_config.versions.get');
    Route::post('/app-config/versions', [AppConfigController::class, 'updateVersions'])->name('admin.api.app_config.versions.update');
    Route::get('/app-config/subscription', [AppConfigController::class, 'getSubscription'])->name('admin.api.app_config.subscription.get');
    Route::post('/app-config/subscription', [AppConfigController::class, 'updateSubscription'])->name('admin.api.app_config.subscription.update');

    // Subscriptions dashboard data
    Route::get('/subscriptions-dashboard/list', [SubscriptionsAdminController::class, 'list'])->name('admin.api.subscriptions_dashboard.list');
    Route::get('/subscriptions-dashboard/stats', [SubscriptionsAdminController::class, 'stats'])->name('admin.api.subscriptions_dashboard.stats');

    // Tariffs API removed

    // Maintenance
    Route::post('/maintenance/gallery/cleanup', [\App\Http\Controllers\Admin\MaintenanceController::class, 'cleanupMissingGallery'])->name('admin.api.maintenance.gallery.cleanup');
    Route::post('/maintenance/truncate', [\App\Http\Controllers\Admin\MaintenanceController::class, 'truncate'])->name('admin.api.maintenance.truncate');
    Route::post('/maintenance/regenerate-thumbs', [\App\Http\Controllers\Admin\MaintenanceController::class, 'regenerateThumbs'])->name('admin.api.maintenance.regenerate_thumbs');

    // Import (admin)
    Route::post('/import/start', [\App\Http\Controllers\Admin\ImportController::class, 'startImport'])->name('admin.api.import.start');
    Route::get('/import/progress/{jobId}', [\App\Http\Controllers\Admin\ImportController::class, 'getProgress'])->name('admin.api.import.progress');
    Route::post('/import/stop/{jobId}', [\App\Http\Controllers\Admin\ImportController::class, 'stop'])->name('admin.api.import.stop');
});

// Admin OTP auth routes (session login)
Route::group(['prefix' => 'admin-auth'], function () {
    Route::post('/request-otp', [\App\Http\Controllers\Admin\AuthController::class, 'requestOtp'])->name('admin.auth.request_otp');
    Route::post('/verify-otp', [\App\Http\Controllers\Admin\AuthController::class, 'verifyOtp'])->name('admin.auth.verify_otp');
});
