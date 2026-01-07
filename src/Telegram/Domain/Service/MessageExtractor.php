<?php

declare(strict_types=1);

namespace App\Telegram\Domain\Service;

use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\Update\Update;

final class MessageExtractor
{
    public function extract(Update $update): Message
    {
        return $update->message
            ?? $update->editedMessage
            ?? $update->callbackQuery?->message
            ?? throw new \Exception('Message not found');
    }
}
