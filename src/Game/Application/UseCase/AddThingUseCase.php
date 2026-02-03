<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Event\ThingHasBeenAdded;
use App\Game\Domain\Exception\ForbiddenActionException;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\ThingIsAlreadyInTheListException;
use App\Game\Domain\Exception\ThingsPlayerLimitReachedException;
use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\ValueObject\Thing;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class AddThingUseCase
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private GameRepository $gameRepository,
    ) {
    }

    /**
     * @throws GameNotFoundException
     * @throws ThingIsAlreadyInTheListException
     * @throws ThingsPlayerLimitReachedException
     * @throws ForbiddenActionException
     */
    public function __invoke(Player $player, Thing $thing): void
    {
        $game = $this->gameRepository->findActiveByPlayer($player)
            ?? throw new GameNotFoundException();

        $game->addThing($player, $thing->value);
        $this->gameRepository->save($game);

        $this->eventDispatcher->dispatch(new ThingHasBeenAdded($player, $game));
    }
}
