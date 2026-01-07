<?php

declare(strict_types=1);

namespace App\Game\Domain\ValueObject;

final readonly class ThingsPerPlayer
{
    public const int MIN_THINGS_PER_PLAYER = 1;
    public const int MAX_THINGS_PER_PLAYER = 5;

    public function __construct(
        private int $value,
    ) {
        if ($value < self::MIN_THINGS_PER_PLAYER || $value > self::MAX_THINGS_PER_PLAYER) {
            throw new \InvalidArgumentException(sprintf('Value of things per player must be from %d to %d', self::MIN_THINGS_PER_PLAYER, self::MAX_THINGS_PER_PLAYER));
        }
    }

    public function getValue(): int
    {
        return $this->value;
    }
}
