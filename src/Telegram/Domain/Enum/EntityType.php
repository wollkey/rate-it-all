<?php

declare(strict_types=1);

namespace App\Telegram\Domain\Enum;

enum EntityType: string
{
    case BotCommand = 'bot_command';
    case Hashtag = 'hashtag';
    case Email = 'email';
    case Bold = 'bold';
}
