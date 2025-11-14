<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            if (!Schema::hasColumn('masters', 'is_premium')) {
                $table->boolean('is_premium')->default(false)->after('working_hours');
            }
            if (!Schema::hasColumn('masters', 'premium_until')) {
                $table->dateTime('premium_until')->nullable()->after('is_premium');
            }
        });
    }

    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            if (Schema::hasColumn('masters', 'premium_until')) {
                $table->dropColumn('premium_until');
            }
            if (Schema::hasColumn('masters', 'is_premium')) {
                $table->dropColumn('is_premium');
            }
        });
    }
};


