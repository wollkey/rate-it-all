<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\ThingIsAlreadyInTheListException;
use App\Game\Domain\Exception\ThingsPlayerLimitReachedException;
use App\Game\Domain\Model\Game;
use App\Game\Domain\ValueObject\Thing;

final readonly class AddThingUseCase
{
    public function __construct(
        private Game $game,
    ) {
    }

    /**
     * @throws GameNotFoundException
     * @throws ThingIsAlreadyInTheListException
     * @throws ThingsPlayerLimitReachedException
     */
    public function __invoke(Player $player, Thing $thing): void
    {
        $gameSession = $this->game->continue($player);

        if ($gameSession->thingExists($thing)) {
            throw new ThingIsAlreadyInTheListException();
        }

        if ($gameSession->playerThingLimitReached($player->getId())) {
            throw new ThingsPlayerLimitReachedException();
        }

        $gameSession->addThing($player, $thing);
        $this->game->saveSession($gameSession);
    }
}
