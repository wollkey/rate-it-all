<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Application\Dto\PlayerDto;
use App\Game\Domain\Model\Game;
use App\Game\Domain\Model\GameSession;
use App\Game\Domain\Repository\PlayerRepositoryInterface;

final readonly class CreateGameUseCase
{
    public function __construct(
        private GameSession $gameSession,
        private PlayerRepositoryInterface $playerRepository,
    ) {
    }

    public function newGame(PlayerDto $playerDto): Game
    {
        $master = $this->playerRepository->find($playerDto->getId());
        $game = $this->gameSession->continueGame($master);

        $newGame = $game !== null
            ? $this->gameSession->restart($game)
            : $this->gameSession->create($master);

        $this->gameSession->save($newGame);

        return $newGame;
    }
}
