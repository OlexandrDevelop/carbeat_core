<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_chat_threads', function (Blueprint $table) {
            $table->string('uuid', 36)->primary();
            $table->foreignId('master_id')->constrained('masters')->cascadeOnDelete();
            $table->string('garage_client_uuid', 36)->nullable();
            $table->string('customer_name');
            $table->string('car_model')->nullable();
            $table->string('plate_number', 50)->nullable();
            $table->text('last_message_preview')->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->boolean('has_photo_request')->default(false);
            $table->timestamp('thread_updated_at')->nullable();
            $table->string('app', 50)->default('carbeat');
            $table->timestamps();

            $table->index(['master_id', 'app', 'thread_updated_at']);
            $table->index(['garage_client_uuid']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_chat_threads');
    }
};
