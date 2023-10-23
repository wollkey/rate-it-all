<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Application\Dto\PlayerDto;
use App\Game\Domain\Model\GameSession;
use App\Game\Domain\Repository\PlayerRepositoryInterface;

final readonly class JoinGameUseCase
{
    public function __construct(
        private GameSession $gameSession,
        private PlayerRepositoryInterface $playerRepository,
    ) {
    }

    public function join(PlayerDto $playerDto, string $gameId): void
    {
        $player = $this->playerRepository->find($playerDto->getId());
        $game = $this->gameSession->get($gameId);

        $this->gameSession->addPlayerToGame($player, $game);
        $this->gameSession->save($game);
    }
}
