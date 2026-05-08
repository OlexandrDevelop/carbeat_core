<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('bay_id')->nullable()->constrained('master_bays')->nullOnDelete();
            $table->decimal('amount', 12, 2);
            $table->string('method');
            $table->string('status')->default('confirmed');
            $table->timestamp('paid_at')->nullable();
            $table->string('external_reference')->nullable();
            $table->string('receipt_url')->nullable();
            $table->text('note')->nullable();
            $table->string('app')->default('carbeat');
            $table->timestamps();

            $table->index(['booking_id', 'status']);
            $table->index(['master_id', 'paid_at']);
            $table->index(['app', 'method', 'status', 'paid_at']);
            $table->index(['client_id', 'paid_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_payments');
    }
};
