<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_runs', function (Blueprint $table) {
            $table->id();
            $table->uuid('job_id')->unique();
            $table->string('source');
            $table->text('url');
            $table->string('app')->nullable();
            $table->string('status')->default('queued');
            $table->unsignedInteger('total_urls')->default(0);
            $table->unsignedInteger('imported_count')->default(0);
            $table->unsignedInteger('matched_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->text('error')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['source', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_runs');
    }
};
