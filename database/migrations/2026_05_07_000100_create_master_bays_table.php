<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_bays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->string('title');
            $table->string('technician_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('display_order')->default(0);
            $table->string('app')->default('carbeat');
            $table->timestamps();

            $table->index(['master_id', 'is_active']);
            $table->index(['app', 'master_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_bays');
    }
};
