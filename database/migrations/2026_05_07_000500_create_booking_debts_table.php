<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('bay_id')->nullable()->constrained('master_bays')->nullOnDelete();
            $table->decimal('original_amount', 12, 2);
            $table->decimal('outstanding_amount', 12, 2);
            $table->string('status')->default('open');
            $table->string('reason')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamp('last_reminded_at')->nullable();
            $table->string('app')->default('carbeat');
            $table->timestamps();

            $table->index(['booking_id', 'status']);
            $table->index(['master_id', 'status', 'due_date']);
            $table->index(['client_id', 'status', 'due_date']);
            $table->index(['app', 'status', 'due_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_debts');
    }
};
