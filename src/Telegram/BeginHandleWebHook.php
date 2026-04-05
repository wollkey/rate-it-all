<?php

declare(strict_types=1);

namespace App\Telegram;

use Symfony\Contracts\EventDispatcher\Event;

final class BeginHandleWebHook extends Event
{
    public function __construct(
        public readonly TelegramInput $telegramInput,
    ) {
    }
}
