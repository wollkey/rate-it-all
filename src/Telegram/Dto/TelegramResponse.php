<?php

declare(strict_types=1);

namespace App\Telegram\Dto;

final readonly class TelegramResponse
{
    public function __construct(
        private Message $message,
    ) {
    }

    public function getMessage(): Message
    {
        return $this->message;
    }
}
