<?php

declare(strict_types=1);

namespace App\Game\Domain\Repository;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Model\Game;

interface GameSessionRepositoryInterface
{
    public function findByPlayer(int $playerId): ?Game;

    public function save(Game $game): void;

    public function find(string $gameId): ?Game;

    public function delete(Game $game): void;

    public function addPlayerToGame(Player $player, string $gameId): void;

    public function removePlayerFromGame(Player $player): void;
}
