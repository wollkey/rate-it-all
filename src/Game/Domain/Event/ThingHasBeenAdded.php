<?php

declare(strict_types=1);

namespace App\Game\Domain\Event;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Game;

final readonly class ThingHasBeenAdded implements DomainEvent
{
    public function __construct(
        public Player $player,
        public Game $game,
    ) {
    }
}
