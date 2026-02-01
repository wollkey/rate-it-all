<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\ThingHasBeenAdded;
use App\Game\Infrastructure\Telegram\Command\StartRatingThing;
use Phptg\BotApi\TelegramBotApi;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class TellPlayerToAddNextThing
{
    public function __construct(
        private TelegramBotApi $telegramApi,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(ThingHasBeenAdded $event): void
    {
        $gameSession = $event->getGameSession();
        $player = $event->getPlayer();

        if (!$gameSession->playerThingLimitReached($player->getId())) {
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('Great, enter the next thing:')
            );

            return;
        }

        $this->telegramBot->stopProcessingCommand($player->getTelegramId());

        $allThingsMessage = $this->translator->trans('Great job! Just waiting on others now...');
        $this->telegramApi->sendMessage($player->getTelegramId(), $allThingsMessage);

        if ($gameSession->totalThingLimitReached()) {
            $this->telegramApi->sendMessage(
                $gameSession->getMaster()->getTelegramId(),
                $this->translator->trans('All players are ready'),
                [
                    'reply_markup' => [
                        'inline_keyboard' => [[
                            [
                                'text' => $this->translator->trans("Let's have some madness!"),
                                'callback_data' => StartRatingThing::COMMAND_NAME,
                            ],
                        ]],
                    ],
                ],
            );
        }
    }
}
