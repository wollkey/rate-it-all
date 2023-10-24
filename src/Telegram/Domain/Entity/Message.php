<?php

declare(strict_types=1);

namespace App\Telegram\Domain\Entity;

use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Annotation\SerializedPath;

final readonly class Message
{
    /**
     * @param array<Entity> $entities
     */
    public function __construct(
        #[SerializedName('message_id')]
        private int $messageId,
        private From $from,
        private Chat $chat,
        private string|null $text = null,
        private array $entities = [],
        #[SerializedPath('[reply_markup][inline_keyboard][callback_data]')]
        private ?string $callbackData = null,
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

    public function getText(): ?string
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

    public function getCallbackData(): ?string
    {
        return $this->callbackData;
    }
}
