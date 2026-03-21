<?php

declare(strict_types=1);

namespace App\Game\Domain\Repository;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\PlayerNotFoundException;

interface PlayerRepository
{
    public function findById(int $id): ?Player;

    /**
     * @throws PlayerNotFoundException
     */
    public function getById(int $id): Player;
}
