<?php

declare(strict_types=1);

namespace App\Game\Domain\Repository;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Game;

interface GameRepository
{
    public function findById(mixed $id): ?Game;

    public function save(Game $game): void;

    public function findActiveByPlayer(Player $player): ?Game;

    public function delete(Game $game): void;
}
