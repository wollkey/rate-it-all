<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Event\NextRatedThingTaken;
use App\Game\Domain\Exception\ForbiddenActionException;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\ThingListIsEmptyException;
use App\Game\Domain\Repository\GameRepository;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class PickNextThingUseCase
{
    public function __construct(
        private GameRepository $gameRepository,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws GameNotFoundException
     * @throws ThingListIsEmptyException
     * @throws ForbiddenActionException
     */
    public function __invoke(Player $player): void
    {
        $game = $this->gameRepository->findActiveByPlayer($player)
            ?? throw new GameNotFoundException();

        $game->nextThing();
        $this->gameRepository->save($game);
        $nextThing = $game->getCurrentThing();

        if ($nextThing === null) {
            $game->finish();
            $this->gameRepository->save($game);
            throw new ThingListIsEmptyException();
        }

        $this->eventDispatcher->dispatch(new NextRatedThingTaken($game));
    }
}
