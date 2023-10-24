<?php

declare(strict_types=1);

namespace App\Telegram\Domain\Entity;

use Symfony\Component\Serializer\Annotation\SerializedName;

final readonly class From
{
    public function __construct(
        private int $id,
        #[SerializedName('is_bot')]
        private bool $isBot,
        #[SerializedName('first_name')]
        private string $firstName,
        private string $username,
        #[SerializedName('last_name')]
        private string|null $lastName = null,
    ) {
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIsBot(): bool
    {
        return $this->isBot;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function getUsername(): string
    {
        return $this->username;
    }
}
