<?php

declare(strict_types=1);

namespace App\Game\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UuidType;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity]
final class Player
{
    #[ORM\Id]
    #[ORM\Column(type: UuidType::NAME, unique: true)]
    private Uuid $id;

    public function __construct(
        #[ORM\Column(type: 'bigint')]
        private readonly int $telegramId,
        #[ORM\Column(length: 255)]
        private string $firstName,
        #[ORM\Column(length: 10, options: ['default' => 'en'])]
        private string $locale = 'en',
        #[ORM\Column(length: 255, nullable: true)]
        private ?string $lastName = null,
    ) {
        $this->id = new UuidV7();
    }

    public function getId(): Uuid
    {
        return $this->id;
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function updateProfile(string $firstName, ?string $lastName): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function changeLocale(string $locale): void
    {
        $this->locale = $locale;
    }

    public function getTelegramId(): int
    {
        return $this->telegramId;
    }
}
