<?php

declare(strict_types=1);

namespace App\Telegram\Domain\EntityExtractor;

use App\Telegram\Domain\Entity\From;
use App\Telegram\Domain\Entity\TelegramRequest;

class UserExtractor implements TelegramEntityExtractor
{
    public function extract(TelegramRequest $telegramRequest): From
    {
        return $telegramRequest->getMessage()?->getFrom()
            ?? $telegramRequest->getEditedMessage()?->getFrom()
            ?? $telegramRequest->getCallbackQuery()?->getFrom()
            ?? throw new \Exception('User (from) not found');
    }
}
