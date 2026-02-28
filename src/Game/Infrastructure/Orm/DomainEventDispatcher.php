<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Orm;

use App\Game\Domain\Aggregate\AggregateRoot;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Events;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsDoctrineListener(event: Events::postPersist)]
#[AsDoctrineListener(event: Events::postUpdate)]
#[AsDoctrineListener(event: Events::postFlush)]
final class DomainEventDispatcher
{
    /**
     * @var list<AggregateRoot>
     */
    private array $entities = [];

    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $this->collect($args->getObject());
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $this->collect($args->getObject());
    }

    public function postFlush(): void
    {
        $entities = $this->entities;
        $this->entities = [];

        foreach ($entities as $entity) {
            foreach ($entity->pullEvents() as $event) {
                $this->eventDispatcher->dispatch($event);
            }
        }
    }

    private function collect(object $entity): void
    {
        if ($entity instanceof AggregateRoot) {
            $this->entities[] = $entity;
        }
    }
}
