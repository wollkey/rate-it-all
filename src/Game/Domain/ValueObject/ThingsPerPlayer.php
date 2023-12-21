<?php

declare(strict_types=1);

namespace App\Game\Domain\ValueObject;

final readonly class ThingsPerPlayer
{
    public function __construct(
        private int $value,
    ) {
        $this->guard($value);
    }

    public function getValue(): int
    {
        return $this->value;
    }

    private function guard(int $value): void
    {
        if ($value < 1 || $value > 5) {
            throw new \InvalidArgumentException('Value of things per player must be from 1 to 5');
        }
    }
}
