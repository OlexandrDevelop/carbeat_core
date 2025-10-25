<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('masters', 'tariff_id')) {
        $freeId = DB::table('tariffs')->where('name', 'free')->value('id');
        if ($freeId) {
            DB::table('masters')->whereNull('tariff_id')->update(['tariff_id' => $freeId]);
        }
    }
    }

    public function down(): void
    {
        // no-op
    }
};
