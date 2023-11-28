<?php

declare(strict_types=1);

namespace App\Game\Domain\Repository;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Model\GameSession;

interface GameSessionRepositoryInterface
{
    public function findByPlayer(int $playerId): ?GameSession;

    public function save(GameSession $gameSession): void;

    public function find(string $gameId): ?GameSession;

    public function delete(GameSession $gameSession): void;

    public function addPlayerToGame(Player $player, string $gameId): void;

    public function removePlayerFromGame(Player $player): void;
}
