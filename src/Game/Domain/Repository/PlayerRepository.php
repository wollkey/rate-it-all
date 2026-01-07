<?php

declare(strict_types=1);

namespace App\Game\Domain\Repository;

use App\Game\Domain\Entity\Player;

interface PlayerRepository
{
    public function find(mixed $id, $lockMode = null, $lockVersion = null): ?Player;
}
