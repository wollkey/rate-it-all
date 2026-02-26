<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\InvalidGameStateException;
use App\Game\Domain\Exception\NoCurrentThingException;
use App\Game\Domain\Exception\PlayerNotInGameException;
use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\ValueObject\Rating;

final readonly class RateThingUseCase
{
    public function __construct(
        private GameRepository $gameRepository,
    ) {
    }

    /**
     * @throws GameNotFoundException
     * @throws InvalidGameStateException
     * @throws NoCurrentThingException
     * @throws PlayerNotInGameException
     * @throws ThingIsAlreadyRatedException
     */
    public function __invoke(Player $player, Rating $rating): void
    {
        $game = $this->gameRepository->findActiveByPlayer($player)
            ?? throw new GameNotFoundException();

        $game->rate($player, $rating->getRating());
        $this->gameRepository->save($game);
    }
}
