<?php

declare(strict_types=1);

namespace App\Telegram\Domain\Event;

use App\Telegram\Application\Dto\TelegramDto;
use Symfony\Contracts\EventDispatcher\Event;

final class BeginHandleWebHook extends Event
{
    public function __construct(
        private readonly TelegramDto $telegramDto,
    ) {
    }

    public function getTelegramDto(): TelegramDto
    {
        return $this->telegramDto;
    }
}
