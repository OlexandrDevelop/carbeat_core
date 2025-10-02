<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->string('currency', 8)->default('USD');
            $table->string('apple_product_id')->nullable();
            $table->string('google_product_id')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tariffs', function (Blueprint $table) {
            $table->dropColumn(['currency', 'apple_product_id', 'google_product_id']);
        });
    }
};
