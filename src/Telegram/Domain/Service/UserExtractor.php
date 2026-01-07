<?php

declare(strict_types=1);

namespace App\Telegram\Domain\Service;

use Phptg\BotApi\Type\Update\Update;
use Phptg\BotApi\Type\User;

final class UserExtractor
{
    public function extract(Update $update): User
    {
        return $update->message?->from
            ?? $update->editedMessage?->from
            ?? $update->callbackQuery?->from
            ?? throw new \Exception('User (from) not found');
    }
}
