<?php

declare(strict_types=1);

namespace App\Game\Domain\Event;

use App\Game\Domain\Game;

final readonly class NextRatedThingTaken
{
    public function __construct(
        public Game $game,
    ) {
    }
}
