<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\NotEnoughPlayersException;
use App\Game\Domain\Exception\OnlyMasterCanStartException;
use App\Game\Domain\Repository\GameRepository;

final readonly class StartGameUseCase
{
    public function __construct(
        private GameRepository $gameRepository,
    ) {
    }

    /**
     * @throws GameNotFoundException|OnlyMasterCanStartException|NotEnoughPlayersException
     */
    public function __invoke(Player $player): void
    {
        $game = $this->gameRepository->findActiveByPlayer($player) ?? throw new GameNotFoundException();

        $game->startCollecting($player);
        $this->gameRepository->save($game);
    }
}
