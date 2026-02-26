<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\MasterCannotLeaveException;
use App\Game\Domain\Repository\GameRepository;

final readonly class LeaveGameUseCase
{
    public function __construct(
        private GameRepository $gameRepository,
    ) {
    }

    /**
     * @throws GameNotFoundException|MasterCannotLeaveException
     */
    public function __invoke(Player $player): void
    {
        $game = $this->gameRepository->findActiveByPlayer($player)
            ?? throw new GameNotFoundException();

        $game->leave($player);

        $this->gameRepository->save($game);
    }
}
