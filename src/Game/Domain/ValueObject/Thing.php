<?php

declare(strict_types=1);

namespace App\Game\Domain\ValueObject;

final readonly class Thing
{
    private const int MIN_LENGTH = 2;

    private string $hash;

    public function __construct(
        public string $value,
    ) {
        $this->validate($value);

        $this->hash = hash('sha256', $value);
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    private function validate(string $value): void
    {
        if (mb_strlen($value) < self::MIN_LENGTH) {
            throw new \InvalidArgumentException();
        }
    }
}
