<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\ThingsRatingIsCompleted;
use App\Game\Infrastructure\Telegram\Command\ShowResultCommand;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class NotifyPlayersAboutRatingIsCompleted
{
    public function __construct(
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(ThingsRatingIsCompleted $event): void
    {
        $gameSession = $event->getGameSession();

        foreach ($gameSession->getPlayers() as $player) {
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('Congrats! You really rated all this nonsense!'),
                [
                    'reply_markup' => [
                        'inline_keyboard' => [[
                            [
                                'text' => $this->translator->trans('Reveal the result'),
                                'callback_data' => ShowResultCommand::COMMAND_NAME,
                            ],
                        ]],
                    ],
                ],
            );

            $this->telegramBot->stopProcessingCommand($player->getTelegramId());
        }
    }
}
