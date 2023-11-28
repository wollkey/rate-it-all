<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\UserResolver;

use App\Game\Domain\Entity\Player;

interface PlayerResolver
{
    public function getPlayer(object $dto): Player;
}
