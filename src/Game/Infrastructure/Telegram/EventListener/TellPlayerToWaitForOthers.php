<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\ThingHasBeenRated;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class TellPlayerToWaitForOthers
{
    public function __construct(
        private TelegramApi $telegramApi,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(ThingHasBeenRated $event): void
    {
        if ($event->isThingFullyRated()) {
            return;
        }

        $this->telegramApi->sendMessage(
            $event->getPlayer()->getTelegramId(),
            $this->translator->trans('Okay.') . ' ' . $this->translator->trans('Wait other players...')
        );
    }
}
