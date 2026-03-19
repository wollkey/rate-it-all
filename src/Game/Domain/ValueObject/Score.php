<?php

declare(strict_types=1);

namespace App\Game\Domain\ValueObject;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Embeddable]
final readonly class Score
{
    private const int MIN = 1;
    private const int MAX = 10;

    /**
     * @param int<1, 10> $value
     */
    public function __construct(
        #[ORM\Column(name: 'score', type: 'smallint')]
        public int $value,
    ) {
        if ($value < self::MIN || $value > self::MAX) {
            throw new \InvalidArgumentException(sprintf('Rating must be from %d to %d, got %d', self::MIN, self::MAX, $value));
        }
    }
}
