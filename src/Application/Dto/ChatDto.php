<?php

declare(strict_types=1);

namespace App\Application\Dto;

use App\Application\Enum\ChatType;
use Symfony\Component\Serializer\Annotation\SerializedName;

final readonly class ChatDto
{
    public function __construct(
        private int $id,
        #[SerializedName('first_name')]
        private string $first_name,
        #[SerializedName('last_name')]
        private string $last_name,
        private string $username,
        private ChatType $type,
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

    public function getLastName(): string
    {
        return $this->last_name;
    }

    public function getUsername(): string
    {
        return $this->username;
    }

    public function getType(): ChatType
    {
        return $this->type;
    }
}
