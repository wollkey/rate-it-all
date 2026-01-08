<?php

declare(strict_types=1);

namespace App\Telegram\Domain\Enum;

enum InputType
{
    case Text;
    case Callback;
}
