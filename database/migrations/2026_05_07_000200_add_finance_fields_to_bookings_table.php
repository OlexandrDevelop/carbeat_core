<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->foreignId('bay_id')->nullable()->after('master_id')->constrained('master_bays')->nullOnDelete();
            $table->string('financial_status')->default('pending')->after('status');
            $table->decimal('total_amount', 12, 2)->default(0)->after('financial_status');
            $table->decimal('paid_amount', 12, 2)->default(0)->after('total_amount');
            $table->decimal('discount_amount', 12, 2)->default(0)->after('paid_amount');
            $table->decimal('parts_cost_total', 12, 2)->default(0)->after('discount_amount');
            $table->decimal('labor_amount', 12, 2)->default(0)->after('parts_cost_total');
            $table->decimal('margin_amount', 12, 2)->default(0)->after('labor_amount');
            $table->timestamp('completed_at')->nullable()->after('end_time');
            $table->timestamp('closed_at')->nullable()->after('completed_at');
            $table->timestamp('payment_due_at')->nullable()->after('closed_at');
            $table->string('invoice_number')->nullable()->after('payment_due_at');

            $table->index(['master_id', 'financial_status', 'start_time'], 'bookings_master_financial_status_start_idx');
            $table->index(['bay_id', 'start_time'], 'bookings_bay_start_idx');
            $table->index(['closed_at'], 'bookings_closed_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropIndex('bookings_master_financial_status_start_idx');
            $table->dropIndex('bookings_bay_start_idx');
            $table->dropIndex('bookings_closed_at_idx');
            $table->dropConstrainedForeignId('bay_id');
            $table->dropColumn([
                'financial_status',
                'total_amount',
                'paid_amount',
                'discount_amount',
                'parts_cost_total',
                'labor_amount',
                'margin_amount',
                'completed_at',
                'closed_at',
                'payment_due_at',
                'invoice_number',
            ]);
        });
    }
};
