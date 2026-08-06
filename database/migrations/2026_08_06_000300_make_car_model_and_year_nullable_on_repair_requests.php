<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A driver often doesn't know the exact model/year of the car that needs
 * repair (or is filing on someone else's behalf) — only car_make, city and
 * the problem description are truly required now.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('repair_requests', function (Blueprint $table) {
            $table->string('car_model')->nullable()->change();
            $table->string('car_year', 4)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('repair_requests', function (Blueprint $table) {
            $table->string('car_model')->nullable(false)->change();
            $table->string('car_year', 4)->nullable(false)->change();
        });
    }
};
