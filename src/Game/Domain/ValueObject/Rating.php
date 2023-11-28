<?php

declare(strict_types=1);

namespace App\Game\Domain\ValueObject;

final readonly class Rating
{
    /**
     * @param positive-int<1, 10> $rating
     */
    public function __construct(private int $rating)
    {
        $this->validate($this->rating);
    }

    public function getRating(): int
    {
        return $this->rating;
    }

    private function validate(int $rating): void
    {
        if ($rating < 1 || $rating > 10) {
            throw new \InvalidArgumentException('You must use only from 1 to 10 numbers');
        }
    }
}
