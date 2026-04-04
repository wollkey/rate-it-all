<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\PlayerLeft;
use App\Telegram\TelegramResponder;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class NotifyPlayersAboutPlayerLeftGame
{
    public function __construct(
        private TranslatorInterface $translator,
        private TelegramResponder $telegramResponder,
    ) {
    }

    public function __invoke(PlayerLeft $event): void
    {
        foreach ($event->game->getPlayers() as $player) {
            $this->telegramResponder->send(
                chatId: $player->getTelegramId(),
                text: $this->translator->trans(
                    'playerName left the game',
                    ['playerName' => $event->player->getFirstName()],
                    locale: $player->getLocale(),
                ),
            );
        }
    }
}
