<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\UserResolver;

use App\Game\Domain\Entity\Player;
use App\Game\Infrastructure\Telegram\Repository\TelegramPlayerRepository;
use App\Telegram\Domain\Entity\From;
use App\Telegram\Domain\Exception\TelegramException;

final class TelegramPlayerResolver implements PlayerResolver
{
    public function __construct(
        private readonly TelegramPlayerRepository $playerRepository,
    ) {
    }

    /**
     * @param From $dto
     */
    public function getPlayer(object $dto): Player
    {
        return $this->playerRepository->find($dto->getId()) ?? throw new TelegramException('Player not found');
    }
}
