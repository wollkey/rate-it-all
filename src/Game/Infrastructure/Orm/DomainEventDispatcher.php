<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Orm;

use App\Game\Domain\Game;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Events;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsEntityListener(event: Events::postFlush, method: 'postFlush', entity: Game::class)]
final readonly class DomainEventDispatcher
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function postFlush(Game $game): void
    {
        foreach ($game->releaseEvents() as $event) {
            $this->eventDispatcher->dispatch($event);
        }
    }
}
