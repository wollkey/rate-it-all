<?php

declare(strict_types=1);

namespace App\Telegram\Domain\Entity;

use App\Telegram\Domain\Enum\ChatType;
use Symfony\Component\Serializer\Annotation\SerializedName;

final readonly class Chat
{
    public function __construct(
        private int $id,
        #[SerializedName('first_name')]
        private string $first_name,
        private string $username,
        private ChatType $type,
        #[SerializedName('last_name')]
        private string|null $lastName = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->first_name;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getType(): ChatType
    {
        return $this->type;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }
}
