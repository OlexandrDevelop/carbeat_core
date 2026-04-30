<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->boolean('is_fake_online')
                ->default(false)
                ->after('status_expires_at');
            $table->timestamp('last_status_update')
                ->nullable()
                ->after('is_fake_online');

            $table->index(['app', 'is_fake_online', 'status']);
            $table->index(['app', 'last_status_update']);
        });
    }

    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->dropIndex(['app', 'is_fake_online', 'status']);
            $table->dropIndex(['app', 'last_status_update']);
            $table->dropColumn(['is_fake_online', 'last_status_update']);
        });
    }
};
