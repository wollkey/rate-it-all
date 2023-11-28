<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\Exception\ThingNotInTheListException;
use App\Game\Domain\Model\Game;
use App\Game\Domain\ValueObject\Rating;

final readonly class RateThingUseCase
{
    public function __construct(
        private Game $game,
    ) {
    }

    /**
     * @throws GameNotFoundException
     * @throws ThingIsAlreadyRatedException
     * @throws ThingNotInTheListException
     */
    public function __invoke(Player $player, Rating $rating): void
    {
        $gameSession = $this->game->continue($player);
        $ratedThing = $gameSession->getCurrentRatedThing();

        if ($ratedThing->alreadyRated($player)) {
            throw new ThingIsAlreadyRatedException();
        }

        $gameSession->rateThing($ratedThing->getThing(), $player, $rating);
        $this->game->saveSession($gameSession);

        // TODO dispatch event
    }
}
