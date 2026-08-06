<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Repair requests are broadcast to every master offering the matching
 * service (App\Http\Services\RepairRequestNotificationService), not owned by
 * one — so "called" / "rejected" is per (master, repair_request) pair, not a
 * column on repair_requests itself. A missing row means "new" (never acted
 * on) — rows are only written once a master takes an action.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_repair_request_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->constrained()->cascadeOnDelete();
            $table->foreignId('repair_request_id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['pending', 'called', 'rejected'])->default('pending');
            $table->timestamp('called_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->unique(['master_id', 'repair_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_repair_request_statuses');
    }
};
