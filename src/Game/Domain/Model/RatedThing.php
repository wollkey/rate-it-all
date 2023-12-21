<?php

declare(strict_types=1);

namespace App\Game\Domain\Model;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\ValueObject\Rating;
use App\Game\Domain\ValueObject\Thing;

final class RatedThing
{
    /**
     * @param array<int, Rating> $playerRatings
     */
    public function __construct(
        private readonly Thing $thing,
        private bool $isRated = false,
        private array $playerRatings = [],
    ) {
    }

    public function getThing(): Thing
    {
        return $this->thing;
    }

    public function isRated(): bool
    {
        return $this->isRated;
    }

    public function rateThing(Player $player, Rating $rating): void
    {
        $this->playerRatings[$player->getId()] = $rating;
        $this->isRated = true;
    }

    public function alreadyRated(Player $player): bool
    {
        return array_key_exists($player->getId(), $this->playerRatings);
    }

    public function countRatedPlayers(): int
    {
        return count($this->playerRatings);
    }

    public function averageRating(): int
    {
        $average = 0;

        foreach ($this->playerRatings as $rating) {
            $average += $rating->getRating();
        }

        return (int) ($average / count($this->playerRatings));
    }
}
