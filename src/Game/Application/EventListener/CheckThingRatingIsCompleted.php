<?php

declare(strict_types=1);

namespace App\Game\Application\EventListener;

use App\Game\Application\UseCase\PickNextThingUseCase;
use App\Game\Domain\Event\TheGameIsOver;
use App\Game\Domain\Event\ThingHasBeenRated;
use App\Game\Domain\Exception\ThingListIsEmptyException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsEventListener]
final readonly class CheckThingRatingIsCompleted
{
    public function __construct(
        private PickNextThingUseCase $pickNextThingUseCase,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function __invoke(ThingHasBeenRated $event): void
    {
        if (!$event->isThingFullyRated) {
            return;
        }

        try {
            ($this->pickNextThingUseCase)($event->player);
        } catch (ThingListIsEmptyException) {
            $this->eventDispatcher->dispatch(new TheGameIsOver($event->game));
        }
    }
}
