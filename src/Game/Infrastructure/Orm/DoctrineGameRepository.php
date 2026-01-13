<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Orm;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Game;
use App\Game\Domain\GameStatus;
use App\Game\Domain\Repository\GameRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Uid\Uuid;

/**
 * @extends ServiceEntityRepository<Game>
 */
final class DoctrineGameRepository extends ServiceEntityRepository implements GameRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Game::class);
    }

    public function findByCode(Uuid $code): ?Game
    {
        return $this->findOneBy(['code' => $code]);
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

    public function findActiveByMaster(Player $player): ?Game
    {
        return $this->createQueryBuilder('g')
            ->where('g.master = (:master)')
            ->andWhere('g.status NOT IN (:status)')
            ->setParameter('master', $player)
            ->setParameter('status', GameStatus::Finished)
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
