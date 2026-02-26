<?php

declare(strict_types=1);

namespace App\Game\Domain\Aggregate;

use App\Game\Domain\Event\DomainEvent;

abstract class AggregateRoot
{
    /**
     * @var list<DomainEvent>
     */
    private array $domainEvents = [];

    /**
     * @return list<DomainEvent>
     */
    public function releaseEvents(): array
    {
        $events = $this->domainEvents;
        $this->domainEvents = [];

        return $events;
    }

    protected function addEvent(DomainEvent $event): void
    {
        $this->domainEvents[] = $event;
    }
}
