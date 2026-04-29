<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->json('extra_info')->nullable()->after('working_hours');
        });
    }

    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->dropColumn('extra_info');
        });
    }
};
