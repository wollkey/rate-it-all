<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\ForbiddenActionException;
use App\Game\Domain\Exception\GameNotFoundException;

final readonly class LeaveGameUseCase
{
    /**
     * @throws GameNotFoundException|ForbiddenActionException
     */
    public function __invoke(Player $player): void
    {
        $gameSession = $this->game->continue($player);

        if ($gameSession->isPlayerMaster($player)) {
            throw new ForbiddenActionException('');
        }

        $this->game->leaveGame($player, $gameSession);
        $this->game->saveSession($gameSession);
    }
}
