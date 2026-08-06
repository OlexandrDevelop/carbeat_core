<?php

namespace App\Http\Services;

use App\Models\Master;
use Illuminate\Support\Str;
use NotificationChannels\Telegram\TelegramMessage;
use Throwable;

/**
 * Links masters to the master-facing Telegram bot (separate from the
 * internal ops bot in TelegramService) and sends them notifications once
 * linked. Masters can't be messaged cold — Telegram's Bot API only allows
 * messaging a chat that has sent /start first — so the flow is: build a
 * deep link with a one-time token, invite the master via SMS
 * (App\Http\Services\RepairRequestNotificationService), then the webhook
 * (App\Http\Controllers\TelegramMastersBotController) fills in chat_id.
 */
class MasterTelegramService
{
    /**
     * Build the t.me deep link carrying the master's link token, generating
     * the token lazily the same way ClaimService lazily generates claim_token.
     */
    public function buildDeepLink(Master $master): string
    {
        if (empty($master->telegram_link_token)) {
            // Short on purpose: this token rides inside a cost-sensitive SMS
            // (single UCS-2 segment = 70 Cyrillic chars total), unlike
            // ClaimService's 40-char claim_token which only appears in a web
            // URL. 8 alphanumeric chars is still ~2*10^14 combinations —
            // plenty for "don't let someone else's notifications land here".
            $master->telegram_link_token = Str::random(8);
            $master->save();
        }

        // No "https://" scheme: it's dead weight in the SMS char budget and
        // t.me links are auto-linkified without it on modern phones anyway.
        return sprintf(
            't.me/%s?start=%s',
            config('services.telegram-masters-bot.username'),
            $master->telegram_link_token
        );
    }

    /**
     * Resolve a master by the token from a /start {token} webhook payload
     * and record their chat_id. Not brand-scoped: the webhook has no X-App
     * context, and the token alone is unique and sufficient to identify
     * the master.
     */
    public function linkChatIdByToken(string $token, string $chatId): ?Master
    {
        $master = Master::withoutGlobalScope('app')
            ->where('telegram_link_token', $token)
            ->first();

        if (! $master) {
            return null;
        }

        $master->forceFill(['telegram_chat_id' => $chatId])->save();

        return $master;
    }

    public function notify(Master $master, string $message): bool
    {
        if (empty($master->telegram_chat_id)) {
            return false;
        }

        try {
            $telegramMessage = TelegramMessage::create()
                ->to($master->telegram_chat_id)
                ->content($message)
                ->options(['parse_mode' => 'HTML']);

            // TelegramMessage::token() only stores the value on the message
            // object for the Notification-channel route (Notification::send())
            // — it's never read by send(), which always goes through the
            // package's Telegram singleton bound to services.telegram-bot-api
            // (the ops bot). Mutate that instance directly via its public
            // $telegram property so this actually sends from our bot, not ops.
            $telegramMessage->telegram->setToken((string) config('services.telegram-masters-bot.token'));

            $telegramMessage->send();

            return true;
        } catch (Throwable $e) {
            logger()->warning('Master telegram notify failed', [
                'master_id' => $master->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
