<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Model\Game;
use App\Game\Domain\Model\GameSession;
use App\Game\Domain\ValueObject\ThingsPerPlayer;

final readonly class CreateGameUseCase
{
    public function __construct(
        private Game $game,
    ) {
    }

    public function __invoke(Player $master, ThingsPerPlayer $thingPerPlayer): GameSession
    {
        $gameSession = $this->game->findSessionByPlayer($master);

        $newGame = $gameSession !== null
            ? $this->game->restartSession($gameSession)
            : $this->game->createSession($master, $thingPerPlayer);

        $this->game->saveSession($newGame);

        return $newGame;
    }
}
