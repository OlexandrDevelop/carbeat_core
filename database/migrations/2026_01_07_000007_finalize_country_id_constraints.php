<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = ['cities','services','masters','clients','users'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                // Set default where null to avoid issues is handled by seeder
                $t->unsignedBigInteger('country_id')->nullable(false)->change();
                $t->foreign('country_id')->references('id')->on('countries')->onDelete('restrict');
            });
        }
    }

    public function down(): void
    {
        $tables = ['cities','services','masters','clients','users'];

        foreach ($tables as $table) {
            Schema::table($table, function (Blueprint $t) use ($table) {
                $t->dropForeign([$table . '_country_id_foreign']);
                $t->unsignedBigInteger('country_id')->nullable()->change();
            });
        }
    }
};

