<?php

declare(strict_types=1);

namespace App\Game\Domain\Event;

use App\Game\Domain\Model\GameSession;

final readonly class NextRatedThingTaken
{
    public function __construct(
        private GameSession $gameSession,
    ) {
    }

    public function getGameSession(): GameSession
    {
        return $this->gameSession;
    }
}
