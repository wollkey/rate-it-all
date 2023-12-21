<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Repository;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Repository\PlayerRepositoryInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class TelegramPlayerRepository extends ServiceEntityRepository implements PlayerRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Player::class);
    }

    public function find(mixed $id, $lockMode = null, $lockVersion = null): Player|null
    {
        return parent::findOneBy(['telegramId' => $id]);
    }
}
