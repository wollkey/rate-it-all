<?php

declare(strict_types=1);

namespace App\Game\Domain\Model;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Repository\GameSessionRepositoryInterface;
use App\Telegram\Domain\Exception\TelegramException;
use Ramsey\Uuid\Uuid;

final readonly class GameSession
{
    public function __construct(
        private GameSessionRepositoryInterface $sessionRepository,
    ) {
    }

    public function create(Player $master): Game
    {
        $newGame = (new Game($this->generateShortUuid(), $master, 2));

        $this->sessionRepository->addPlayerToGame($master, $newGame->getId());

        return $newGame;
    }

    private function generateShortUuid(): string
    {
        return Uuid::uuid7()->getTimeHiAndVersionHex();
    }

    public function restart(Game $game): Game
    {
        $this->sessionRepository->delete($game);

        return $this->create($game->getMaster());
    }

    public function get(string $gameId): Game
    {
        return
            $this->sessionRepository->find($gameId)
            ?? throw new TelegramException('The game with this id not found');
    }

    public function continueGame(Player $player): ?Game
    {
        return $this->sessionRepository->findByPlayer($player->getId());
    }

    public function save(Game $game): void
    {
        $this->sessionRepository->save($game);
    }

    public function addPlayerToGame(Player $player, Game $game): void
    {
        $game->addPlayer($player);
        $this->sessionRepository->addPlayerToGame($player, $game->getId());
    }

    public function leaveGame(Player $player, Game $game): void
    {
        $game->removePlayer($player);
        $this->sessionRepository->removePlayerFromGame($player);
    }

    public function finishGame(Game $game)
    {
        foreach ($game->getPlayers() as $player) {
            $this->sessionRepository->removePlayerFromGame($player);
        }

        $this->sessionRepository->delete($game);
    }
}
