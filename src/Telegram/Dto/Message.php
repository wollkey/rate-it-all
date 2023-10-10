<?php

declare(strict_types=1);

namespace App\Telegram\Dto;

use Symfony\Component\Serializer\Annotation\SerializedName;

final readonly class Message
{
    /**
     * @param array<Entity> $entities
     */
    public function __construct(
        #[SerializedName('message_id')]
        private int    $messageId,
        private From   $from,
        private Chat   $chat,
        private string $text,
        private array  $entities = [],
    ) {
    }

    public function getMessageId(): int
    {
        return $this->messageId;
    }

    public function getFrom(): From
    {
        return $this->from;
    }

    public function getChat(): Chat
    {
        return $this->chat;
    }

    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return array<Entity>
     */
    public function getEntities(): array
    {
        return $this->entities;
    }
}
