<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Game;
use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\ValueObject\ThingsPerPlayer;

final readonly class CreateGameUseCase
{
    public function __construct(
        private GameRepository $gameRepository,
    ) {
    }

    public function __invoke(Player $master, ThingsPerPlayer $thingsPerPlayer): Game
    {
        $game = $this->gameRepository->findActiveByPlayer($master);

        if (null !== $game) {
            return $game;
        }

        $game = new Game(
            master: $master,
            thingsPerPlayer: $thingsPerPlayer,
        );

        $game->join($master);
        $this->gameRepository->save($game);

        return $game;
    }
}
