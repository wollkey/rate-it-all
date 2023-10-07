<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateway;

use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

final class TelegramApi
{
    public function __construct(
        private readonly Telegram $telegram,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function setWebHook(string $url): void
    {
        try {
            $this->telegram->setWebhook($url);
        } catch (TelegramException $exception) {
            throw new \Exception("Webhook was not set because of error {$exception->getMessage()}");
        }
    }

    public function sendMessage(string $text): string
    {
        $response = $this->telegram->handleGetUpdates();

        if (!$response->isOk()) {
            $response->printError();
        }

        $response = Request::sendMessage([
            'chat_id' => 0,
            'text' => $text,
        ]);

        return $text;
    }
}
