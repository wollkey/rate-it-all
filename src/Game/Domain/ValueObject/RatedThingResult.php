<?php

declare(strict_types=1);

namespace App\Game\Domain\ValueObject;

final readonly class RatedThingResult
{
    /**
     * @param non-empty-string $thing
     */
    public function __construct(
        public string $thing,
        public float $averageScore,
    ) {
    }
}
