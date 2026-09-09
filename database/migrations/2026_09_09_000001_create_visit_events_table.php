<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Individual pageview/click log rows for a visit_sessions row. Kept
 * separate from the session summary so the click timeline can grow without
 * touching the (frequently-listed) sessions table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visit_events', function (Blueprint $table) {
            $table->id();
            $table->string('app');
            $table->foreignId('visit_session_id')->constrained('visit_sessions')->cascadeOnDelete();
            $table->string('type', 20);
            $table->string('path', 2048)->nullable();
            $table->string('full_url', 2048)->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->string('element_tag', 20)->nullable();
            $table->string('element_text', 255)->nullable();
            $table->string('element_id')->nullable();
            $table->string('element_classes', 255)->nullable();
            $table->string('element_href', 2048)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at');

            $table->index(['visit_session_id', 'created_at']);
            $table->index(['app', 'type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visit_events');
    }
};
