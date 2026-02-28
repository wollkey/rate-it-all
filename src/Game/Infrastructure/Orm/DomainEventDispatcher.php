<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Orm;

use App\Game\Domain\Aggregate\AggregateRoot;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostFlushEventArgs;
use Doctrine\ORM\Events;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsDoctrineListener(event: Events::postFlush)]
final readonly class DomainEventDispatcher
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function postFlush(PostFlushEventArgs $args): void
    {
        $uow = $args->getObjectManager()->getUnitOfWork();

        foreach ($uow->getIdentityMap() as $entities) {
            foreach ($entities as $entity) {
                if ($entity instanceof AggregateRoot) {
                    foreach ($entity->pullEvents() as $event) {
                        $this->eventDispatcher->dispatch($event);
                    }
                }
            }
        }
    }
}
