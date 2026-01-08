<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Orm;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Game;
use App\Game\Domain\GameStatus;
use App\Game\Domain\Repository\GameRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Game>
 */
final class DoctrineGameRepository extends ServiceEntityRepository implements GameRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function findById(mixed $id): ?Game
    {
        return $this->find($id);
    }

    public function save(Game $game): void
    {
        $this->getEntityManager()->persist($game);
        $this->getEntityManager()->flush();
    }

    public function findActiveByPlayer(Player $player): ?Game
    {
        return $this->createQueryBuilder('g')
            ->innerJoin('g.players', 'p')
            ->where('p = :player')
            ->andWhere('g.status IN (:statuses)')
            ->setParameter('player', $player)
            ->setParameter('statuses', [
                GameStatus::Waiting,
                GameStatus::Collecting,
                GameStatus::Rating,
            ])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function delete(Game $game): void
    {
        $this->getEntityManager()->remove($game);
        $this->getEntityManager()->flush();
    }
}
