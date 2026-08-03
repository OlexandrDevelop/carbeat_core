<?php

namespace App\Http\Services;

use JsonException;
use NotificationChannels\Telegram\Exceptions\CouldNotSendNotification;
use NotificationChannels\Telegram\TelegramMessage;
use Throwable;

/**
 * Class TelegramService
 *
 * Service for sending messages to a Telegram chat using the Telegram Bot API.
 */
class TelegramService
{
    private const int MAX_TELEGRAM_MESSAGE_LENGTH = 4096;

    /**
     * Sends a message to a Telegram chat.
     *
     * @param  string  $message  The message content to send.
     *
     * @throws CouldNotSendNotification
     * @throws JsonException
     */
    public function send(string $message): void
    {
        $chunks = str_split($message, self::MAX_TELEGRAM_MESSAGE_LENGTH);

        foreach ($chunks as $chunk) {
            $this->sendChunk($chunk);
        }
    }

    /**
     * Reports an exception to the Telegram chat.
     *
     * @param  Throwable  $exception  The exception to report.
     *
     * @throws CouldNotSendNotification
     * @throws JsonException
     */
    public function report(Throwable $exception): void
    {
        // parse_mode HTML rejects the whole message if any interpolated text
        // contains something that looks like an unsupported tag (e.g. stack
        // traces routinely contain "Object.<anonymous>"), so exception
        // content must be escaped — only the surrounding literal tags stay.
        $message = sprintf(
            "<b>Error on server (%s)</b>\n\n<b>Message:</b> %s\n<b>File:</b> %s:%d\n\n<code>%s</code>",
            e(config('app.env')),
            e($exception->getMessage()),
            e($exception->getFile()),
            $exception->getLine(),
            e($exception->getTraceAsString())
        );

        $this->send($message);
    }

    /**
     * Sends a single message chunk to Telegram.
     *
     * @param  string  $chunk  The message chunk.
     *
     * @throws CouldNotSendNotification
     * @throws JsonException
     */
    private function sendChunk(string $chunk): void
    {
        TelegramMessage::create()
            ->token(config('services.telegram-bot-api.token'))
            ->to(config('services.telegram-bot-api.chat_id'))
            ->content($chunk)
            ->options(['parse_mode' => 'HTML'])
            ->send();
    }
}
