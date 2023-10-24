<?php

declare(strict_types=1);

namespace App\Telegram\Domain\Entity;

use App\Telegram\Domain\Enum\EntityType;

final readonly class Entity
{
    public function __construct(
        private int $offset,
        private int $length,
        private EntityType $type,
    ) {
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    public function getType(): EntityType
    {
        return $this->type;
    }
}
