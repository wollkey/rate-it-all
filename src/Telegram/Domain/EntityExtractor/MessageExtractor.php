<?php

declare(strict_types=1);

namespace App\Telegram\Domain\EntityExtractor;

use App\Telegram\Domain\Entity\Message;
use App\Telegram\Domain\Entity\TelegramRequest;

class MessageExtractor implements TelegramEntityExtractor
{
    public function extract(TelegramRequest $telegramRequest): Message
    {
        return $telegramRequest->getMessage()
            ?? $telegramRequest->getEditedMessage()
            ?? $telegramRequest->getCallbackQuery()?->getMessage()
            ?? throw new \Exception('Message not found');
    }
}
