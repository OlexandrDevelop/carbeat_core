<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guest_push_callbacks', function (Blueprint $table) {
            $table->id();
            $table->string('guest_device_id', 128);
            $table->string('token', 512);
            $table->string('platform', 32)->nullable();
            $table->string('app', 32)->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->softDeletes();
            $table->timestamps();

            $table->index(['guest_device_id', 'app']);
            $table->index(['app', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guest_push_callbacks');
    }
};
