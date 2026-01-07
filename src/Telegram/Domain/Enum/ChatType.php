<?php

declare(strict_types=1);

namespace App\Telegram\Domain\Enum;

enum ChatType: string
{
    case Private = 'private';
    case Group = 'group';
}
