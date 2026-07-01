<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TelegramService
{
    private ?string $botToken;

    public function __construct()
    {
        $this->botToken = config('services.telegram.bot_token');
    }

    /**
     * Send a message to a Telegram chat.
     * Fail-silent: returns false on failure, never throws to caller.
     */
    public function sendMessage(string $chatId, string $message): bool
    {
        if (empty($this->botToken)) {
            Log::info('Telegram bot token not configured, skipping notification.');
            return false;
        }

        try {
            $response = Http::post("https://api.telegram.org/bot{$this->botToken}/sendMessage", [
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'HTML',
            ]);

            if ($response->failed()) {
                Log::warning('Telegram API returned error: ' . $response->body());
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('Telegram notification failed: ' . $e->getMessage());
            return false;
        }
    }
}
