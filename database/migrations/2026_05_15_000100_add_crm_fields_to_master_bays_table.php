<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_bays', function (Blueprint $table) {
            $table->string('uuid', 36)->nullable()->unique()->after('id');
            $table->string('status', 20)->default('free')->after('display_order');
        });
    }

    public function down(): void
    {
        Schema::table('master_bays', function (Blueprint $table) {
            $table->dropUnique(['uuid']);
            $table->dropColumn(['uuid', 'status']);
        });
    }
};
