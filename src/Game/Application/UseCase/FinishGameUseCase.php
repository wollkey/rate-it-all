<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\OnlyMasterCanFinishException;
use App\Game\Domain\Repository\GameRepository;

final readonly class FinishGameUseCase
{
    public function __construct(
        private GameRepository $gameRepository,
    ) {
    }

    /**
     * @throws GameNotFoundException|OnlyMasterCanFinishException
     */
    public function __invoke(Player $player): void
    {
        $game = $this->gameRepository->findActiveByPlayer($player)
            ?? throw new GameNotFoundException();

        if (!$game->isMaster($player)) {
            throw new OnlyMasterCanFinishException($game);
        }

        $game->finish();
        $this->gameRepository->save($game);
    }
}
