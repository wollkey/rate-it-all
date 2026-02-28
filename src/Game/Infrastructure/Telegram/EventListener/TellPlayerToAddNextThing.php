<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\ThingAdded;
use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use App\Telegram\TelegramResponder;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class TellPlayerToAddNextThing
{
    public function __construct(
        private TranslatorInterface $translator,
        private TelegramResponder $telegramResponder,
        private ConversationStorage $conversations,
    ) {
    }

    public function __invoke(ThingAdded $event): void
    {
        if ($event->game->isPlayerThingLimitReached($event->player)) {
            $this->telegramResponder->send(
                chatId: $event->player->getTelegramId(),
                text: $this->translator->trans('Great job! Just waiting on others now...'),
            );
            $this->conversations->clear($event->player->getTelegramId());
        } else {
            $this->telegramResponder->send(
                chatId: $event->player->getTelegramId(),
                text: $this->translator->trans('Great, enter the next thing:'),
            );
        }
    }
}
