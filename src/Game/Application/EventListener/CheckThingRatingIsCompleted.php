<?php

declare(strict_types=1);

namespace App\Game\Application\EventListener;

use App\Game\Application\UseCase\TakeNextThingUseCase;
use App\Game\Domain\Event\ThingHasBeenRated;
use App\Game\Domain\Event\ThingsRatingIsCompleted;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\ThingListIsEmptyException;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsEventListener]
final readonly class CheckThingRatingIsCompleted
{
    public function __construct(
        private TakeNextThingUseCase $takeNextThingUseCase,
        private EventDispatcherInterface $eventDispatcher,
    ) {
    }

    /**
     * @throws GameNotFoundException
     */
    public function __invoke(ThingHasBeenRated $event): void
    {
        if (!$event->isThingFullyRated()) {
            return;
        }

        $player = $event->getPlayer();

        try {
            ($this->takeNextThingUseCase)($player);
        } catch (ThingListIsEmptyException) {
            $this->eventDispatcher->dispatch(new ThingsRatingIsCompleted($player, $event->getGameSession()));
        }
    }
}
