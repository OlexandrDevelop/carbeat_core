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
            // Backfill: any tariff that is not 'free' marks master as premium.
            // Written as a portable subquery (not a MySQL-specific UPDATE...JOIN)
            // so it also runs on the SQLite in-memory DB used by the test suite.
            DB::statement("
                UPDATE masters
                SET is_premium = 1
                WHERE tariff_id IN (
                    SELECT id FROM tariffs WHERE name IS NOT NULL AND name <> 'free'
                )
            ");
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('masters') && Schema::hasColumn('masters', 'is_premium')) {
            DB::statement('UPDATE masters SET is_premium = 0 WHERE is_premium = 1');
        }
    }
};
