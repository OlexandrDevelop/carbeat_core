<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_status_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('driver_user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->string('status', 32)->default('pending');
            $table->string('channel', 32)->default('sms');
            $table->string('answer', 32)->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('notification_message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['master_id', 'status']);
            $table->index(['driver_user_id', 'master_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_status_requests');
    }
};
