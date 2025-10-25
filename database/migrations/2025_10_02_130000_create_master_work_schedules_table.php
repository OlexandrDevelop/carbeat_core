<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('master_work_schedules')) {
        Schema::create('master_work_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_of_week'); // 0=Sun ... 6=Sat
            $table->time('start_time');
            $table->time('end_time');
            $table->boolean('active')->default(true);
            $table->timestamps();

            $table->index(['master_id', 'day_of_week']);
        });
    }
    }

    public function down(): void
    {
        Schema::dropIfExists('master_work_schedules');
    }
};
