<?php

namespace App\Http\Controllers;

use App\Http\Services\MasterTelegramService;
use App\Http\Services\RepairRequestNotificationService;
use App\Models\RepairRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Receives Telegram webhook updates for the master-facing bot (registered
 * via App\Console\Commands\SetTelegramMastersBotWebhook). Currently only
 * handles "/start {token}" — the deep link a master taps after receiving
 * the SMS invite (App\Http\Services\RepairRequestNotificationService) —
 * which links their chat_id so future notifications are free.
 */
class TelegramMastersBotController extends Controller
{
    public function handle(
        Request $request,
        MasterTelegramService $telegramService,
        RepairRequestNotificationService $notificationService
    ): JsonResponse {
        $expectedSecret = config('services.telegram-masters-bot.webhook_secret');
        if (! empty($expectedSecret)) {
            abort_unless(
                hash_equals((string) $expectedSecret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token')),
                401
            );
        }

        $text = (string) $request->input('message.text', '');
        $chatId = $request->input('message.chat.id');

        if ($chatId && str_starts_with($text, '/start ')) {
            $token = trim(substr($text, 7));
            $master = $telegramService->linkChatIdByToken($token, (string) $chatId);

            if ($master) {
                $repairRequest = $master->last_invited_repair_request_id
                    ? RepairRequest::withoutGlobalScope('app')->find($master->last_invited_repair_request_id)
                    : null;

                $message = $repairRequest
                    ? "✅ Підключено! Ось заявка, через яку ми вам писали:\n\n".$notificationService->buildTelegramMessage($repairRequest)
                    : '✅ Готово! Сповіщення про нові заявки надходитимуть сюди.';

                $telegramService->notify($master, $message);
            }
        }

        // Telegram requires a fast 200 response regardless of outcome — a
        // non-200 or slow response makes it retry the same update.
        return response()->json(['ok' => true]);
    }
}
