<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Event\PlayerHasJoined;
use App\Game\Infrastructure\Telegram\Command\StartGameCommand;
use Phptg\BotApi\TelegramBotApi;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class NotifyMasterAboutJoinedPlayer
{
    public function __construct(
        private TranslatorInterface $translator,
        private TelegramBotApi $telegramApi,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(PlayerHasJoined $event): void
    {
        $gameSession = $event->getGameSession();

        $masterTelegramId = $gameSession->getMaster()->getTelegramId();
        $editedMessage = $this->telegramBot->getEditedMessage($masterTelegramId);

        $this->telegramApi->editMessage(
            $masterTelegramId,
            $editedMessage->getMessageId(),
            implode(PHP_EOL, [
                $this->translator->trans('Players joined the game:'),
                ...array_map(
                    static fn (Player $player) => $player->getFirstName(),
                    $gameSession->getPlayers(),
                ),
                '',
                $this->translator->trans('As soon as you are ready, start the game'),
            ]),
            [
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => $this->translator->trans('Start the game'),
                                'callback_data' => StartGameCommand::COMMAND_NAME,
                            ],
                        ],
                    ],
                ],
            ],
        );
    }
}
