<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Manual moderation gate: a new request no longer notifies masters
 * immediately (App\Http\Controllers\RepairRequestController::verifyAndSubmit()
 * now just alerts the ops Telegram chat) — an admin must approve it first
 * (App\Http\Controllers\Admin\RepairRequestController::approve()), which is
 * the only thing that actually triggers
 * App\Http\Services\RepairRequestNotificationService::notify(). The driver
 * sees the same "request received" response either way.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_requests', function (Blueprint $table) {
            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('longitude');
            $table->timestamp('approved_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('repair_requests', function (Blueprint $table) {
            $table->dropColumn(['status', 'approved_at']);
        });
    }
};
