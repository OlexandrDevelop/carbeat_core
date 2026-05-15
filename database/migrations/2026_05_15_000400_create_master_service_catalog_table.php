<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_service_catalog', function (Blueprint $table) {
            $table->string('uuid', 36)->primary();
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->string('name_uk');
            $table->string('name_en');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->unsignedInteger('price_uah')->default(0);
            $table->unsignedInteger('display_order')->default(0);
            $table->string('app', 50)->default('carbeat');
            $table->timestamps();

            $table->index(['master_id', 'app', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_service_catalog');
    }
};
