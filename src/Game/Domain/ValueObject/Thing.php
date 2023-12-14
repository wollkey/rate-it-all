<?php

declare(strict_types=1);

namespace App\Game\Domain\ValueObject;

final readonly class Thing
{
    private const MIN_STRING_LENGTH = 2;

    private string $value;
    private string $hash;

    public function __construct(string $value)
    {
        $this->validate($value);

        $this->value = $value;
        $this->hash = hash('sha256', $value);
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getHash(): string
    {
        return $this->hash;
    }

    private function validate(string $value): void
    {
        if (mb_strlen($value) < self::MIN_STRING_LENGTH) {
            throw new \InvalidArgumentException();
        }
    }
}
