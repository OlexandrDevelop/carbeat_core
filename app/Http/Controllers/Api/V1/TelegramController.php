<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use NotificationChannels\Telegram\TelegramMessage;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class TelegramController extends Controller
{
    public static function toTelegram(Throwable $e, $request): JsonResponse
    {
        $userId = '';
        $userName = '';
        $ip = '';
        if (Auth::check()) {
            $userId = Auth::user()->user_code;
            $userName = Auth::user()->name;
        }
        $application = config('app.name');
        $environment = config('app.env');
        $methodType = $request->method();
        $ip = '';
        $url = $request->url();
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        $message = "*{$application}({$environment})*"."\n";
        $message .= "*Request ({$methodType}):* ".$url."\n";
        $message .= "*Error ({$statusCode}):* ".$e->getMessage()."\n";
        $message .= '*Module:* '.$e->getFile()."\n";
        $message .= '*Line:* '.$e->getLine()."\n";
        $message .= '*User:* '.$userName.' ('.$userId.')'."\n";

        if ($methodType === 'POST') {
            $postParams = json_encode($request->all(), JSON_PRETTY_PRINT);
            $message .= '*Post Parameters:*';
            $message .= "```json\n".$postParams."\n```";
        }

        $message = str_replace(['_', '[', '`'], ['\_', '\[', '\`'], $message);
        $chunks = str_split($message, 4096);

        $recipients = User::pluck('telegram')->toArray();
        $recipient = config('services.telegram-bot-api.group_id');

        $notifications = [];
        foreach ($recipients as $recipient) {
            if ($recipient) {
                foreach ($chunks as $chunk) {
                    $notification = TelegramController::sendMessageToTelegram($recipient, $chunk);
                    $notifications[] = $notification;
                }
            }
        }

        return response()->json(['message' => 'Повідомлення відправлено користувачам Telegram', 'notifications' => $notifications]);
    }

    private static function sendMessageToTelegram($recipient, $message): array|ResponseInterface|null
    {
        try {
            return TelegramMessage::create()
                ->to($recipient)
                ->line($message)
                ->send();
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            $response = json_decode($e->getResponse()->getBody()->getContents(), true);
            if ($e->getCode() === 429 && isset($response['parameters']['retry_after'])) {
                sleep($response['parameters']['retry_after']);

                return self::sendMessageToTelegram($recipient, $message);
            }
            throw $e; // Перенаправляємо виняток, якщо це не 429
        }
    }

    public function via($notifiable): array
    {
        return ['telegram'];
    }
}
