<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_garage_clients', function (Blueprint $table) {
            $table->string('uuid', 36)->primary();
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->foreignId('platform_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('name');
            $table->string('phone', 50)->nullable();
            $table->string('app', 50)->default('carbeat');
            $table->timestamps();

            $table->index(['master_id', 'app']);
            $table->index(['platform_client_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_garage_clients');
    }
};
