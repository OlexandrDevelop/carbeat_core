<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * One-time/per-deploy setup: tells Telegram where to POST updates for the
 * master-facing bot (App\Http\Controllers\TelegramMastersBotController).
 * Needs a public HTTPS URL, so this only works against staging/production —
 * not plain localhost.
 */
class SetTelegramMastersBotWebhook extends Command
{
    protected $signature = 'telegram:masters-bot:set-webhook {url : Public HTTPS URL of the deployed app, e.g. https://carbeat.online}';

    protected $description = 'Register the Telegram webhook for the master-facing notifications bot';

    public function handle(): int
    {
        $token = config('services.telegram-masters-bot.token');
        if (empty($token)) {
            $this->error('TELEGRAM_MASTERS_BOT_TOKEN is not configured.');

            return self::FAILURE;
        }

        $webhookUrl = rtrim($this->argument('url'), '/').'/api/telegram/masters-bot/webhook';
        $secret = config('services.telegram-masters-bot.webhook_secret');

        $response = Http::post("https://api.telegram.org/bot{$token}/setWebhook", array_filter([
            'url' => $webhookUrl,
            'secret_token' => $secret ?: null,
        ]));

        if (! $response->successful() || ! ($response->json('ok') ?? false)) {
            $this->error('Failed to set webhook: '.$response->body());

            return self::FAILURE;
        }

        $this->info("Webhook registered: {$webhookUrl}");

        return self::SUCCESS;
    }
}
