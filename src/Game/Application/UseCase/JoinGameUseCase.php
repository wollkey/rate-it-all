<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Event\PlayerHasJoined;
use App\Game\Domain\Exception\GameException;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Repository\GameRepository;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class JoinGameUseCase
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private GameRepository $gameRepository,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws GameNotFoundException
     * @throws GameException
     */
    public function __invoke(Player $player, Uuid $gameCode): void
    {
        $game = $this->gameRepository->findByCode($gameCode)
            ?? throw new GameNotFoundException($this->translator->trans('The game with this ID not found'));

        $game->join($player);
        $this->gameRepository->save($game);

        $this->eventDispatcher->dispatch(new PlayerHasJoined($player, $game));
    }
}
