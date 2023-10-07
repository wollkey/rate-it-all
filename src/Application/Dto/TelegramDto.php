<?php

declare(strict_types=1);

namespace App\Application\Dto;

final readonly class TelegramDto
{
    public function __construct(
        private MessageDto $message,
    ) {
    }

    public function getMessage(): MessageDto
    {
        return $this->message;
    }
}
