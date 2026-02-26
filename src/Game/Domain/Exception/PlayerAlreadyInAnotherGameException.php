<?php

declare(strict_types=1);

namespace App\Game\Domain\Exception;

use App\Game\Domain\Game;

final class PlayerAlreadyInAnotherGameException extends GameException
{
    public function __construct(
        public readonly Game $game,
    ) {
        parent::__construct();
    }
}
