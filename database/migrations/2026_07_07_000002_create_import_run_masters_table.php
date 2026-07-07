<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_run_masters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('import_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            // Snapshots kept alongside the FKs so the row still reads meaningfully
            // if the master/city is later renamed, merged or deleted.
            $table->string('master_name')->nullable();
            $table->string('city_name')->nullable();
            $table->string('status');
            $table->string('skip_reason')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['import_run_id', 'status']);
            $table->index(['import_run_id', 'city_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_run_masters');
    }
};
