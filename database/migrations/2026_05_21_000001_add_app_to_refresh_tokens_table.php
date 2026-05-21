<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('refresh_tokens') && ! Schema::hasColumn('refresh_tokens', 'app')) {
            Schema::table('refresh_tokens', function (Blueprint $table) {
                $table->string('app', 50)->default('carbeat')->index()->after('id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('refresh_tokens') && Schema::hasColumn('refresh_tokens', 'app')) {
            Schema::table('refresh_tokens', function (Blueprint $table) {
                $table->dropColumn('app');
            });
        }
    }
};
