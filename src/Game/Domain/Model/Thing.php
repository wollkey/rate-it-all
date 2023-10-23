<?php

declare(strict_types=1);

namespace App\Game\Domain\Model;

final readonly class Thing
{
    private string $value;

    public function __construct(string $value)
    {
        $this->validate($value);

        $this->value = $value;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    private function validate(string $value): void
    {
        if (strlen($value) < 2) {
            throw new \InvalidArgumentException();
        }
    }
}
