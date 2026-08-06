<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tracks which repair request triggered a master's most recent SMS invite,
 * so that when they finally press /start on the Telegram bot
 * (App\Http\Controllers\TelegramMastersBotController), the confirmation
 * message can show them the actual request that prompted the SMS — not just
 * a generic "you're linked now" message.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->foreignId('last_invited_repair_request_id')
                ->nullable()
                ->after('repair_request_sms_invite_last_sent_at')
                ->constrained('repair_requests')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->dropConstrainedForeignId('last_invited_repair_request_id');
        });
    }
};
