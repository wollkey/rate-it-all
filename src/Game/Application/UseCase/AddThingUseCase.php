<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\InvalidGameStateException;
use App\Game\Domain\Exception\PlayerNotInGameException;
use App\Game\Domain\Exception\ThingIsAlreadyInTheListException;
use App\Game\Domain\Exception\ThingListIsEmptyException;
use App\Game\Domain\Exception\ThingsPlayerLimitReachedException;
use App\Game\Domain\Exception\ThingValueTooShortException;
use App\Game\Domain\Repository\GameRepository;

final readonly class AddThingUseCase
{
    public function __construct(
        private GameRepository $gameRepository,
    ) {
    }

    /**
     * @throws GameNotFoundException
     * @throws InvalidGameStateException
     * @throws PlayerNotInGameException
     * @throws ThingIsAlreadyInTheListException
     * @throws ThingValueTooShortException
     * @throws ThingsPlayerLimitReachedException
     * @throws ThingListIsEmptyException
     */
    public function __invoke(Player $player, string $thing): void
    {
        $game = $this->gameRepository->findActiveByPlayer($player)
            ?? throw new GameNotFoundException();

        $game->addThing($player, $thing);
        $this->gameRepository->save($game);
    }
}
