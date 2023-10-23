<?php

declare(strict_types=1);

namespace App\Game\Application\Dto;

final readonly class PlayerDto
{
    public function __construct(
        private string $id,
    ) {
    }

    public function getId(): string
    {
        return $this->id;
    }
}
