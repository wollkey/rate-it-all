<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\InvalidGameStateException;
use App\Game\Domain\Exception\PlayerAlreadyInAnotherGameException;
use App\Game\Domain\Exception\PlayerAlreadyInCurrentGameException;
use App\Game\Domain\Repository\GameRepository;
use Symfony\Component\Uid\Uuid;

final readonly class JoinGameUseCase
{
    public function __construct(
        private GameRepository $gameRepository,
    ) {
    }

    /**
     * @throws GameNotFoundException
     * @throws PlayerAlreadyInAnotherGameException
     * @throws InvalidGameStateException
     * @throws PlayerAlreadyInCurrentGameException
     */
    public function __invoke(Player $player, Uuid $gameCode): void
    {
        $game = $this->gameRepository->getByCode($gameCode);
        $existingGame = $this->gameRepository->findActiveByPlayer($player);

        if ($existingGame !== null && $existingGame->getIdOrFail() !== $game->getIdOrFail()) {
            throw new PlayerAlreadyInAnotherGameException($existingGame);
        }

        $game->join($player);
        $this->gameRepository->save($game);
    }
}
