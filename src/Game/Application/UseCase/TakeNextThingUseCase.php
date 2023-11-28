<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\ThingListIsEmptyException;
use App\Game\Domain\Model\Game;

final readonly class TakeNextThingUseCase
{
    public function __construct(
        private Game $game,
    ) {
    }

    /**
     * @throws GameNotFoundException
     * @throws ThingListIsEmptyException
     */
    public function __invoke(Player $player): void
    {
        $gameSession = $this->game->continue($player);
        $randomThing = $gameSession->getRandomUnratedThing();

        if ($randomThing === null) {
            throw new ThingListIsEmptyException();
        }

        $gameSession->setCurrentRatedThing($randomThing);
        $this->game->saveSession($gameSession);
    }
}
