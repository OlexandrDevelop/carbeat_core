<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per browser tab "session" on the public site, identified by a
 * client-generated token kept in sessionStorage (see useVisitTracking.ts).
 * Counters are updated in place as visit_events come in, so the admin list
 * can render without aggregating the events table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('app');
            $table->uuid('session_token')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->string('device_type', 20)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->string('referrer_host')->nullable();
            $table->string('landing_path', 2048)->nullable();
            $table->unsignedInteger('pageviews_count')->default(0);
            $table->unsignedInteger('clicks_count')->default(0);
            $table->timestamp('started_at');
            $table->timestamp('last_seen_at');
            $table->timestamps();

            $table->index(['app', 'last_seen_at']);
            $table->index(['app', 'referrer_host']);
            $table->index(['app', 'ip_address']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_sessions');
    }
};
