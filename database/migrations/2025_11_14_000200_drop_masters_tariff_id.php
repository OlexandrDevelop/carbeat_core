<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('masters') && Schema::hasColumn('masters', 'tariff_id')) {
            Schema::table('masters', function (Blueprint $table) {
                $table->dropForeign(['tariff_id']);
                $table->dropColumn('tariff_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('masters') && ! Schema::hasColumn('masters', 'tariff_id')) {
            Schema::table('masters', function (Blueprint $table) {
                $table->unsignedBigInteger('tariff_id')->nullable()->after('service_id');
            });
            if (Schema::hasTable('tariffs')) {
                Schema::table('masters', function (Blueprint $table) {
                    $table->foreign('tariff_id')->references('id')->on('tariffs');
                });
            }
        }
    }
};


