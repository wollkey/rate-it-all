<?php

declare(strict_types=1);

namespace App\Telegram\Domain\Event;

use App\Telegram\TelegramDto;
use Symfony\Contracts\EventDispatcher\Event;

final class BeginHandleWebHook extends Event
{
    public function __construct(
        public readonly TelegramDto $telegramDto,
    ) {
    }
}
