<?php

declare(strict_types=1);

namespace App\Game\Domain\Event;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Model\GameSession;

final readonly class ThingHasBeenRated
{
    public function __construct(
        private Player $player,
        private GameSession $gameSession,
        private bool $isThingFullyRated = false,
    ) {
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getGameSession(): GameSession
    {
        return $this->gameSession;
    }

    public function isThingFullyRated(): bool
    {
        return $this->isThingFullyRated;
    }
}
