<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One Telegram account can legitimately be behind more than one master
 * profile (duplicate imports, one person running several listings), and the
 * webhook (App\Http\Controllers\TelegramMastersBotController) has no way to
 * detect that in advance — it was hitting an unhandled 500 via the unique
 * constraint the moment a second master profile linked the same chat_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->dropUnique('masters_telegram_chat_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('masters', function (Blueprint $table) {
            $table->unique('telegram_chat_id');
        });
    }
};
