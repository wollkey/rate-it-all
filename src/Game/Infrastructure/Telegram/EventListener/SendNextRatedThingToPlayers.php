<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\NextThingPicked;
use App\Game\Infrastructure\Telegram\Keyboard\RatingKeyboardFactory;
use App\Telegram\TelegramResponder;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class SendNextRatedThingToPlayers
{
    public function __construct(
        private TranslatorInterface $translator,
        private TelegramResponder $telegramResponder,
        private RatingKeyboardFactory $ratingKeyboardFactory,
    ) {
    }

    public function __invoke(NextThingPicked $event): void
    {
        foreach ($event->game->getPlayers() as $player) {
            $this->telegramResponder->send(
                chatId: $player->getTelegramId(),
                text: '🤔 '.$this->translator->trans(
                    'Rate the next thing: anyThing',
                    ['anyThing' => $event->game->getCurrentThingOrFail()->getValue()],
                    locale: $player->getLocale(),
                ),
                keyboardMarkup: $this->ratingKeyboardFactory->create(),
            );
        }
    }
}
