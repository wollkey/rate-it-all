<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\RatingStarted;
use App\Game\Infrastructure\Telegram\Keyboard\RatingKeyboardFactory;
use App\Telegram\TelegramResponder;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class NotifyPlayersAboutGameRatingStarted
{
    public function __construct(
        private TelegramResponder $telegramResponder,
        private TranslatorInterface $translator,
        private RatingKeyboardFactory $ratingKeyboardFactory,
    ) {
    }

    public function __invoke(RatingStarted $event): void
    {
        foreach ($event->game->getPlayers() as $player) {
            $this->telegramResponder->send(
                chatId: $player->getTelegramId(),
                text: $this->translator->trans('All things collected! Rating begins!').' ⚡',
            );

            $this->telegramResponder->send(
                chatId: $player->getTelegramId(),
                text: '🤔 '.$this->translator->trans(
                    'Rate the thing: anyThing',
                    ['anyThing' => $event->game->getCurrentThing()->getValue()],
                ),
                keyboardMarkup: $this->ratingKeyboardFactory->create(),
            );
        }
    }
}
