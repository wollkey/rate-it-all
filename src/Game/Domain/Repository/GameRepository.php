<?php

declare(strict_types=1);

namespace App\Game\Domain\Repository;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Game;
use Symfony\Component\Uid\Uuid;

interface GameRepository
{
    public function findByCode(Uuid $code): ?Game;

    public function save(Game $game): void;

    public function findActiveByPlayer(Player $player): ?Game;

    public function findActiveByMaster(Player $player): ?Game;

    public function delete(Game $game): void;
}
