<?php

declare(strict_types=1);

namespace App\Game\Domain\Repository;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Game;
use Symfony\Component\Uid\Uuid;

interface GameRepository
{
    /**
     * @throws GameNotFoundException
     */
    public function get(Uuid $id): Game;

    public function save(Game $game): void;

    public function delete(Game $game): void;

    public function findActiveByPlayer(Player $player): ?Game;

    public function findActiveByMaster(Player $player): ?Game;
}
