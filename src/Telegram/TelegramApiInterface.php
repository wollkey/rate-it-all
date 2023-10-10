<?php

declare(strict_types=1);

namespace App\Telegram;

interface TelegramApiInterface
{
    public function setWebhook(string $url): void;

    public function sendMessage(int $chatId, string $text);
}
