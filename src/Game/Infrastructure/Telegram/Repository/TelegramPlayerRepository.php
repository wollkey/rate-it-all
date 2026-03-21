<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Repository;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\PlayerNotFoundException;
use App\Game\Domain\Repository\PlayerRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Player>
 */
final class TelegramPlayerRepository extends ServiceEntityRepository implements PlayerRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    public function findById(int $id): ?Player
    {
        return parent::findOneBy(['telegramId' => $id]);
    }

    public function getById(int $id): Player
    {
        return parent::findOneBy(['telegramId' => $id]) ?? throw new PlayerNotFoundException();
    }
}
