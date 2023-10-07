<?php

declare(strict_types=1);

namespace App\Application\Enum;

enum ChatType: string
{
    case Private = 'private';
    case Group = 'group';
    case Supergroup = 'supergroup';
    case Channel = 'channel';
}
