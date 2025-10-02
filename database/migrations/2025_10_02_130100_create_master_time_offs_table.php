<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_time_offs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->index(['master_id', 'start_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_time_offs');
    }
};
