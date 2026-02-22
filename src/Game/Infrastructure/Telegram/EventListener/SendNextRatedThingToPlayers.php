<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\NextRatedThingTaken;
use App\Telegram\TelegramResponder;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class SendNextRatedThingToPlayers
{
    public function __construct(
        private TranslatorInterface $translator,
        private TelegramResponder $telegramResponder,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(NextRatedThingTaken $event): void
    {
        $keyboardMarkup = new InlineKeyboardMarkup([
            array_map(
                fn (string $i) => new InlineKeyboardButton(
                    text: $i,
                    callbackData: $i,
                ),
                range(1, 5),
            ),
            array_map(
                fn (string $i) => new InlineKeyboardButton(
                    text: $i,
                    callbackData: $i,
                ),
                range(6, 10),
            ),
        ]);

        foreach ($event->game->getPlayers() as $player) {
            $this->telegramResponder->send(
                chatId: $player->getTelegramId(),
                text: $this->translator->trans(
                    'Rate the next thing: anyThing',
                    ['anyThing' => $event->game->getCurrentThing()->getValue()]
                ),
                keyboardMarkup: $keyboardMarkup,
            );
        }
    }
}
