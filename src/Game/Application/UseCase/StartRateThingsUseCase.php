<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Event\NextRatedThingTaken;
use App\Game\Domain\Exception\GameException;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\ThingListIsEmptyException;
use App\Game\Domain\Repository\GameRepository;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class StartRateThingsUseCase
{
    public function __construct(
        private GameRepository $gameRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws GameNotFoundException
     * @throws ThingListIsEmptyException
     * @throws GameException
     */
    public function __invoke(Player $player): void
    {
        $game = $this->gameRepository->findActiveByPlayer($player)
            ?? throw new GameNotFoundException();

        $game->startRating();
        $this->gameRepository->save($game);

        $this->eventDispatcher->dispatch(new NextRatedThingTaken($game));
    }
}
