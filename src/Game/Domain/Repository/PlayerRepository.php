<?php

declare(strict_types=1);

namespace App\Game\Domain\Repository;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\PlayerNotFoundException;

interface PlayerRepository
{
    public function find(mixed $id, $lockMode = null, $lockVersion = null): ?Player;

    /**
     * @throws PlayerNotFoundException
     */
    public function get(mixed $id): Player;
}
