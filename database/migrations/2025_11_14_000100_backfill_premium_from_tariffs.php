<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('masters') && Schema::hasTable('tariffs')
            && Schema::hasColumn('masters', 'tariff_id')
            && Schema::hasColumn('masters', 'is_premium')
        ) {
            // Backfill: any tariff that is not 'free' marks master as premium
            DB::statement("
                UPDATE masters m
                JOIN tariffs t ON t.id = m.tariff_id
                SET m.is_premium = 1
                WHERE t.name IS NOT NULL AND t.name <> 'free'
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('masters') && Schema::hasColumn('masters', 'is_premium')) {
            DB::statement("UPDATE masters SET is_premium = 0 WHERE is_premium = 1");
        }
    }
};


