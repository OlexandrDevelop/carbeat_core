<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_garage_vehicles', function (Blueprint $table) {
            $table->string('uuid', 36)->primary();
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->string('garage_client_uuid', 36)->nullable();
            $table->string('model_name');
            $table->string('plate_number', 50);
            $table->string('app', 50)->default('carbeat');
            $table->timestamps();

            $table->index(['master_id', 'app']);
            $table->index(['garage_client_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_garage_vehicles');
    }
};
