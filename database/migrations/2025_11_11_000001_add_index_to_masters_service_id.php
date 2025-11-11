<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            if (!Schema::hasColumn('masters', 'service_id')) {
                return;
            }
            // Add index if not exists (safe for MySQL 8 via try-catch at runtime, but Laravel doesn't support IF NOT EXISTS here)
            // We'll guard by index name commonly generated.
            try {
                $table->index('service_id', 'masters_service_id_index');
            } catch (\Throwable $e) {
                // ignore if exists
            }
        });
    }

    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            try {
                $table->dropIndex('masters_service_id_index');
            } catch (\Throwable $e) {
                // ignore
            }
        });
    }
};


