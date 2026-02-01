<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Event\ThingHasBeenAdded;
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
     */
    public function __invoke(Player $player, Thing $thing): void
    {
        $gameSession = $this->gameRepository->continue($player);

        if ($gameSession->thingExists($thing)) {
            throw new ThingIsAlreadyInTheListException();
        }

        if ($gameSession->playerThingLimitReached($player->getId())) {
            throw new ThingsPlayerLimitReachedException();
        }

        $gameSession->addThing($player, $thing);
        $this->game->saveSession($gameSession);

        $this->eventDispatcher->dispatch(new ThingHasBeenAdded($player, $gameSession));
    }
}
