<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_daily_reports', function (Blueprint $table) {
            $table->id();
            $table->date('summary_date');
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->foreignId('bay_id')->nullable()->constrained('master_bays')->nullOnDelete();
            $table->string('app')->default('carbeat');
            $table->unsignedInteger('bookings_count')->default(0);
            $table->unsignedInteger('completed_bookings_count')->default(0);
            $table->unsignedInteger('debt_bookings_count')->default(0);
            $table->unsignedInteger('payments_count')->default(0);
            $table->decimal('gross_revenue', 12, 2)->default(0);
            $table->decimal('paid_revenue', 12, 2)->default(0);
            $table->decimal('outstanding_revenue', 12, 2)->default(0);
            $table->decimal('cash_revenue', 12, 2)->default(0);
            $table->decimal('card_revenue', 12, 2)->default(0);
            $table->decimal('qr_revenue', 12, 2)->default(0);
            $table->decimal('transfer_revenue', 12, 2)->default(0);
            $table->decimal('mixed_revenue', 12, 2)->default(0);
            $table->decimal('discount_total', 12, 2)->default(0);
            $table->decimal('parts_cost_total', 12, 2)->default(0);
            $table->decimal('labor_revenue_total', 12, 2)->default(0);
            $table->decimal('margin_total', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['app', 'summary_date', 'master_id', 'bay_id'], 'finance_daily_reports_scope_unique');
            $table->index(['summary_date', 'app']);
            $table->index(['master_id', 'summary_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_daily_reports');
    }
};
