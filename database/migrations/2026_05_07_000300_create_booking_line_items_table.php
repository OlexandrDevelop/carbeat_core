<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_line_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->foreignId('bay_id')->nullable()->constrained('master_bays')->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained('services')->nullOnDelete();
            $table->string('line_type');
            $table->string('category')->nullable();
            $table->string('name');
            $table->decimal('quantity', 10, 2)->default(1);
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('line_total', 12, 2)->default(0);
            $table->decimal('line_cost_total', 12, 2)->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('app')->default('carbeat');
            $table->timestamps();

            $table->index(['booking_id', 'sort_order']);
            $table->index(['master_id', 'line_type', 'created_at']);
            $table->index(['app', 'category', 'line_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_line_items');
    }
};
