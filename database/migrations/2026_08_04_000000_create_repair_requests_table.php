<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Driver-submitted repair requests from the public web form (Carbeat only —
 * see App\Http\Middleware\EnsureCarbeatBrand). Verified via SMS OTP before
 * creation, so `user_id` is always set once the driver's phone is confirmed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('repair_requests', function (Blueprint $table) {
            $table->id();
            $table->string('app');
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('car_make');
            $table->string('car_model');
            $table->string('car_year', 4);
            $table->text('description');
            $table->string('phone');
            $table->string('name');
            $table->timestamps();

            $table->index(['app', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('repair_requests');
    }
};
