<?php

namespace App\Http\Services;

use Daaner\TurboSMS\Facades\TurboSMS;
use Illuminate\Support\Facades\Cache;


class SmsService
{
    public function generateAndSendCode(string $phone, int $length = 4): string
    {
        $code = str_pad(strval(random_int(0, pow(10, $length) - 1)), $length, '0', STR_PAD_LEFT);
        // Cache for 5 minutes by requirement
        Cache::put('sms_code_'.$phone, $code, now()->addMinutes(5));

        $res = TurboSMS::sendMessages($phone, $code);
        $telegramService = new TelegramService();

        // Create formatted message for Telegram
        $message = $this->formatSmsResultForTelegram($phone, $code, $res);
        $telegramService->send($message);

        return $code;
    }

    public function verifyCode(string $phone, string $inputCode): bool
    {
        // Universal override code for testing/support (config-driven)
        $universal = (string) (config('otp.universal_code') ?? '');
        if (is_string($universal) && $universal !== '' && hash_equals($universal, $inputCode)) {
            return true;
        }
        // Local bypass (configurable)
        $enableLocalBypass = (bool) (config('otp.enable_local_bypass') ?? true);
        $localBypass = (string) (config('otp.local_bypass_code') ?? '0000');
        if ($enableLocalBypass && app()->environment('local') && $localBypass !== '' && hash_equals($localBypass, $inputCode)) {
            return true;
        }
        $cachedCode = Cache::get('sms_code_'.$phone);

        return $cachedCode == $inputCode;
    }

    /**
     * Format SMS result for Telegram notification
     */
    private function formatSmsResultForTelegram(string $phone, string $code, $result): string
    {
        $timestamp = now()->format('Y-m-d H:i:s');
        $success = $result['success'] ?? false;

        // Create a beautiful formatted message
        $message = "🔔 <b>SMS Notification</b>\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

        // Basic info
        $message .= "📅 <b>Time:</b> <code>{$timestamp}</code>\n";
        $message .= "📞 <b>Phone:</b> <code>{$phone}</code>\n";
        $message .= "🔢 <b>Code:</b> <code>{$code}</code>\n";

        if ($success) {
            $message .= "✅ <b>Status:</b> <code>Success</code>\n\n";
            $message .= "🎉 <b>Message sent successfully!</b>";
        } else {
            $message .= "❌ <b>Status:</b> <code>Failed</code>\n\n";

            // Extract and display error info
            $errorInfo = $result['info'] ?? 'Unknown error';
            $message .= "⚠️ <b>Error:</b> <code>{$errorInfo}</code>\n\n";

            // Create a clean JSON representation
            $cleanResult = $this->cleanSmsResult($result);
            $formattedJson = json_encode($cleanResult, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            $message .= "📋 <b>Response Details:</b>\n";
            $message .= "<pre><code>{$formattedJson}</code></pre>";
        }

        return $message;
    }

    /**
     * Clean SMS result for better display
     */
    private function cleanSmsResult($result): array
    {
        $clean = [];

        if (isset($result['success'])) {
            $clean['success'] = $result['success'];
        }

        if (isset($result['info'])) {
            $clean['message'] = $result['info'];
        }

        if (isset($result['result']['body'])) {
            $clean['request'] = [
                'recipients' => $result['result']['body']['recipients'] ?? null,
                'sender' => $result['result']['body']['sms']['sender'] ?? null,
                'text_length' => strlen($result['result']['body']['sms']['text'] ?? '')
            ];
        }

        return $clean;
    }
}
