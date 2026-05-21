<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('masters') && ! Schema::hasColumn('masters', 'available')) {
            Schema::table('masters', function (Blueprint $table) {
                $table->boolean('available')->default(false)->after('status_expires_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('masters') && Schema::hasColumn('masters', 'available')) {
            Schema::table('masters', function (Blueprint $table) {
                $table->dropColumn('available');
            });
        }
    }
};
