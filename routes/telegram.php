<?php

use App\Http\Controllers\TelegramMastersBotController;
use Illuminate\Support\Facades\Route;

// Telegram webhook for the master-facing bot — lives under the `api`
// middleware group (via api_v1.php) so it's CSRF/session-free, matching
// Telegram's plain JSON POST contract.
Route::post('/telegram/masters-bot/webhook', [TelegramMastersBotController::class, 'handle'])
    ->name('telegram.masters_bot.webhook');
