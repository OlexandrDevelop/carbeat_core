<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Services\TelegramService;
use Mockery;
use Tests\TestCase;

class TelegramServiceTest extends TestCase
{
    public function test_send_short_message_calls_telegram_once(): void
    {
        $msgMock = Mockery::mock('alias:NotificationChannels\\Telegram\\TelegramMessage');
        $msgMock->shouldReceive('create')->once()->andReturnSelf();
        $msgMock->shouldReceive('token')->once()->andReturnSelf();
        $msgMock->shouldReceive('to')->once()->andReturnSelf();
        $msgMock->shouldReceive('content')->once()->with('Hello')->andReturnSelf();
        $msgMock->shouldReceive('options')->once()->with(['parse_mode' => 'HTML'])->andReturnSelf();
        $msgMock->shouldReceive('send')->once();

        $service = new TelegramService;
        $service->send('Hello');
    }

    public function test_send_long_message_splits_into_chunks(): void
    {
        $long = str_repeat('A', 5000); // >4096
        $expectedChunks = 2;

        $msgMock = Mockery::mock('alias:NotificationChannels\\Telegram\\TelegramMessage');
        $msgMock->shouldReceive('create')->times($expectedChunks)->andReturnSelf();
        $msgMock->shouldReceive('token')->times($expectedChunks)->andReturnSelf();
        $msgMock->shouldReceive('to')->times($expectedChunks)->andReturnSelf();
        // content called with chunk strings; allow any string
        $msgMock->shouldReceive('content')->times($expectedChunks)->andReturnSelf();
        $msgMock->shouldReceive('options')->times($expectedChunks)->andReturnSelf();
        $msgMock->shouldReceive('send')->times($expectedChunks);

        $service = new TelegramService;
        $service->send($long);
    }

    public function test_report_escapes_html_special_characters_from_exception(): void
    {
        // Regression: stack traces routinely contain things like
        // "Object.<anonymous>", which Telegram's parse_mode=HTML rejects
        // outright as an unsupported tag unless escaped.
        $exception = new \RuntimeException('boom <script>alert(1)</script>');

        $msgMock = Mockery::mock('alias:NotificationChannels\\Telegram\\TelegramMessage');
        $msgMock->shouldReceive('create')->once()->andReturnSelf();
        $msgMock->shouldReceive('token')->once()->andReturnSelf();
        $msgMock->shouldReceive('to')->once()->andReturnSelf();
        $msgMock->shouldReceive('options')->once()->andReturnSelf();
        $msgMock->shouldReceive('content')
            ->once()
            ->with(Mockery::on(function (string $content) {
                return str_contains($content, '&lt;script&gt;')
                    && ! str_contains($content, '<script>');
            }))
            ->andReturnSelf();
        $msgMock->shouldReceive('send')->once();

        $service = new TelegramService;
        $service->report($exception);
    }
}
