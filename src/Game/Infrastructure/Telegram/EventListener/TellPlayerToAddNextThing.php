<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\ThingHasBeenAdded;
use App\Game\Infrastructure\Telegram\Handler\StartRatingThing;
use App\Telegram\TelegramResponder;
use Phptg\BotApi\TelegramBotApi;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class TellPlayerToAddNextThing
{
    public function __construct(
        private TelegramBotApi $telegramApi,
        private TranslatorInterface $translator,
        private TelegramResponder $telegramResponder,
    ) {
    }

    public function __invoke(ThingHasBeenAdded $event): void
    {
        if (!$event->game->isPlayerThingLimitReached($event->player)) {
            $this->telegramApi->sendMessage(
                $event->player->getTelegramId(),
                $this->translator->trans('Great, enter the next thing:')
            );

            return;
        }

        $allThingsMessage = $this->translator->trans('Great job! Just waiting on others now...');
        $this->telegramApi->sendMessage($event->player->getTelegramId(), $allThingsMessage);

        if ($event->game->isTotalThingLimitReached()) {
            // TODO Сделать автоматический переход игры в следующий статус как только все игроки добавят вещи
            // Можно для этого добавить событие в самом агрегате Game или в useCase
            $this->telegramResponder->send(
                chatId: $event->game->getMaster()->getTelegramId(),
                text: $this->translator->trans('All players are ready'),
                keyboardMarkup: new InlineKeyboardMarkup([
                    [
                        new InlineKeyboardButton(
                            text: $this->translator->trans("Let's have some madness!"),
                            callbackData: StartRatingThing::COMMAND_NAME,
                        ),
                    ],
                ]),
            );
        }
    }
}
