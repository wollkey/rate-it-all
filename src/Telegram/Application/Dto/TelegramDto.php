<?php

declare(strict_types=1);

namespace App\Telegram\Application\Dto;

use App\Telegram\Domain\Entity\From;
use App\Telegram\Domain\Entity\Message;

final readonly class TelegramDto
{
    public function __construct(
        private From $user,
        private Message $message,
        private string|null $data = null,
    ) {
    }

    public function getUser(): From
    {
        return $this->user;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getData(): ?string
    {
        return $this->data;
    }
}
