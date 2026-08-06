<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Supports the SMS-invite -> Telegram-bot notification funnel for masters
 * who have no app/account (see App\Http\Services\MasterTelegramService and
 * App\Http\Services\RepairRequestNotificationService): telegram_link_token
 * is handed out in the SMS deep-link, telegram_chat_id is filled in by the
 * bot webhook once the master presses "Start". The invite counter caps how
 * many paid SMS invites a master gets before we stop bothering them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->string('telegram_chat_id')->nullable()->unique()->after('claim_token');
            $table->string('telegram_link_token')->nullable()->unique()->after('telegram_chat_id');
            $table->unsignedInteger('repair_request_sms_invite_count')->default(0)->after('telegram_link_token');
            $table->timestamp('repair_request_sms_invite_last_sent_at')->nullable()->after('repair_request_sms_invite_count');
        });
    }

    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->dropColumn([
                'telegram_chat_id',
                'telegram_link_token',
                'repair_request_sms_invite_count',
                'repair_request_sms_invite_last_sent_at',
            ]);
        });
    }
};
