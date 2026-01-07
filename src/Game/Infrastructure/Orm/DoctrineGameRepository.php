<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Orm;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Game;
use App\Game\Domain\Repository\GameRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

final class DoctrineGameRepository extends ServiceEntityRepository implements GameRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function find(mixed $id, $lockMode = null, $lockVersion = null): ?Game
    {
        return parent::findOneBy(['telegramId' => $id]);
    }

    public function save(Game $gameSession): void
    {
        // TODO: Implement save() method.
    }

    public function findActiveByPlayer(Player $player): ?Game
    {
        // TODO: Implement findByPlayer() method.
    }

    public function delete(Game $game): void
    {
        // TODO: Implement delete() method.
    }
}
