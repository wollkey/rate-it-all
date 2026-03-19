<?php

declare(strict_types=1);

namespace App\Game\Domain\Entity;

use App\Game\Domain\Repository\PlayerRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PlayerRepository::class)]
final class Player
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\Column(type: 'bigint')]
        private readonly int $telegramId,
        #[ORM\Column(length: 255)]
        private string $firstName,
        #[ORM\Column(length: 255, nullable: true)]
        private ?string $lastName = null,
    ) {
    }

    public function getId(): ?int
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

    public function getTelegramId(): int
    {
        return $this->telegramId;
    }

    public function updateProfile(string $firstName, ?string $lastName): void
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
    }
}
