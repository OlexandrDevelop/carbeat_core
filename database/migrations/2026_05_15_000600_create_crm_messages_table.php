<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_messages', function (Blueprint $table) {
            $table->string('uuid', 36)->primary();
            $table->string('thread_uuid', 36);
            $table->string('direction', 20); // incoming | outgoing
            $table->string('kind', 20)->default('text'); // text | photo
            $table->text('body');
            $table->timestamp('message_created_at');
            $table->timestamps();

            $table->index(['thread_uuid', 'message_created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_messages');
    }
};
