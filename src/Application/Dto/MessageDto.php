<?php

declare(strict_types=1);

namespace App\Application\Dto;

use Symfony\Component\Serializer\Annotation\SerializedName;

final readonly class MessageDto
{
    /**
     * @param Entity[] $entities
     */
    public function __construct(
        #[SerializedName('message_id')]
        private int $messageId,
        private FromDto $from,
        private ChatDto $chat,
        private string $text,
        private array $entities = [],
    ) {
    }

    public function getMessageId(): int
    {
        return $this->messageId;
    }

    public function getFrom(): FromDto
    {
        return $this->from;
    }

    public function getChat(): ChatDto
    {
        return $this->chat;
    }

    public function getText(): string
    {
        return $this->text;
    }

    /**
     * @return Entity[]
     */
    public function getEntities(): array
    {
        return $this->entities;
    }
}
