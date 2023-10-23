<?php

declare(strict_types=1);

namespace App\Telegram\Domain\Entity;

final readonly class CallbackQuery
{
    public function __construct(
        private From $from,
        private Message $message,
        private string $data,
    ) {
    }

    public function getFrom(): From
    {
        return $this->from;
    }

    public function getMessage(): Message
    {
        return $this->message;
    }

    public function getData(): string
    {
        return $this->data;
    }
}
