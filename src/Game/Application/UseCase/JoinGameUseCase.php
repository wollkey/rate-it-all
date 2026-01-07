<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Event\PlayerHasJoined;
use App\Game\Domain\Exception\GameException;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Repository\GameRepository;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class JoinGameUseCase
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private GameRepository $gameRepository,
    ) {
    }

    /**
     * @throws GameNotFoundException
     * @throws GameException
     */
    public function join(Player $player, string $gameId): void
    {
        $gameSession = $this->gameRepository->find($gameId) ?? throw new GameNotFoundException($this->translator->trans('The game with this ID not found'));

        $this->game->addPlayerToGameSession($player, $gameSession);
        $this->game->saveSession($gameSession);

        $this->eventDispatcher->dispatch(new PlayerHasJoined($player, $gameSession));
    }
}
