<?php

declare(strict_types=1);

namespace App\Game\Domain\Event;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Model\GameSession;

final readonly class ThingsRatingIsCompleted
{
    public function __construct(
        private Player $player,
        private GameSession $gameSession,
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
}
