<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\CollectingStarted;
use App\Game\Infrastructure\Telegram\Handler\AddThing;
use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use App\Telegram\TelegramResponder;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class NotifyPlayersAboutGameCollectingStarted
{
    public function __construct(
        private ConversationStorage $conversations,
        private TelegramResponder $telegramResponder,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(CollectingStarted $event): void
    {
        foreach ($event->game->getPlayers() as $player) {
            $this->conversations->save(
                chatId: $player->getTelegramId(),
                handlerClass: AddThing::class,
            );

            $this->telegramResponder->send(
                chatId: $player->getTelegramId(),
                text: '💡 '.$this->translator->trans('Add any crazy thing that came into your head:'),
            );
        }
    }
}
