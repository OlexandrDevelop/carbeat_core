<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('easyweek_connections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_id')->unique()->constrained('masters')->cascadeOnDelete();
            $table->string('app', 50)->default('carbeat');
            $table->string('easyweek_company_slug');
            $table->unsignedBigInteger('easyweek_company_id')->nullable();
            // Cached from the company config so the frequent availability poll
            // doesn't need to re-fetch the whole widget config every time.
            $table->unsignedBigInteger('primary_branch_id')->nullable();
            $table->unsignedBigInteger('primary_product_id')->nullable();
            $table->string('timezone', 100)->nullable();
            $table->string('sync_status', 20)->default('pending'); // pending | active | error
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamp('last_availability_synced_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('consecutive_failures')->default(0);
            $table->timestamp('failure_notified_at')->nullable();
            $table->timestamps();

            $table->unique(['app', 'easyweek_company_slug']);
            $table->index('sync_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('easyweek_connections');
    }
};
