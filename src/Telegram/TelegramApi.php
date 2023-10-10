<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Telegram\Exception\TelegramException;
use Longman\TelegramBot\Exception\TelegramException as ExternalTelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;

final readonly class TelegramApi implements TelegramApiInterface
{
    public function __construct(
        private Telegram $telegram,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function setWebHook(string $url): void
    {
        try {
            $this->telegram->setWebhook($url);
        } catch (ExternalTelegramException $exception) {
            throw new TelegramException("Webhook was not set because of error {$exception->getMessage()}");
        }
    }

    /**
     * @throws \Exception
     */
    public function sendMessage(int $chatId, string $text): string
    {
        try {
            $response = Request::sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
            ]);
        } catch (ExternalTelegramException $exception) {
            throw new TelegramException("Message was not sent because of error {$exception->getMessage()}");
        }

        if (!$response->isOk()) {
            $response->printError();
        }

        return $text;
    }
}
