<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\ThingHasBeenRated;
use App\Telegram\TelegramResponder;
use Phptg\BotApi\TelegramBotApi;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class TellPlayerToWaitForOthers
{
    public function __construct(
        private TelegramResponder $telegramResponder,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(ThingHasBeenRated $event): void
    {
        if ($event->isThingFullyRated) {
            $this->telegramResponder->send(
                $event->player->getTelegramId(),
                $this->translator->trans('Great job! Wait for the results...')
            );
        }

        $this->telegramResponder->send(
            $event->player->getTelegramId(),
            $this->translator->trans('Great job! Just waiting on others now...')
        );
    }
}
