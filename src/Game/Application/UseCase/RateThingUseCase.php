<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Event\ThingHasBeenRated;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\ValueObject\Rating;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final readonly class RateThingUseCase
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private GameRepository $gameRepository,
    ) {
    }

    /**
     * @throws GameNotFoundException
     * @throws ThingIsAlreadyRatedException
     */
    public function __invoke(Player $player, Rating $rating): void
    {
        $game = $this->gameRepository->findActiveByPlayer($player)
            ?? throw new GameNotFoundException();

        $ratedThing = $game->getCurrentThing();
        $ratedThing->rate($player, $rating->getRating());
        $this->gameRepository->save($game);

        $this->eventDispatcher->dispatch(new ThingHasBeenRated($player, $game, $game->isCurrentThingFullyRated()));
    }
}
