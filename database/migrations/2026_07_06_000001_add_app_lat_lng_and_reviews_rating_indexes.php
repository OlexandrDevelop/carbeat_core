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
        Schema::table('masters', function (Blueprint $table) {
            $table->index(['app', 'latitude', 'longitude'], 'idx_app_lat_lon');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index(['master_id', 'rating'], 'idx_reviews_master_rating');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->dropIndex('idx_app_lat_lon');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('idx_reviews_master_rating');
        });
    }
};
