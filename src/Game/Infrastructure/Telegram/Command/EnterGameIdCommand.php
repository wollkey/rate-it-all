<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\JoinGameUseCase;
use App\Game\Domain\Entity\Player;
use App\Game\Domain\Model\Game;
use App\Game\Infrastructure\UserResolver\PlayerResolver;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EnterGameIdCommand implements BotCommandInterface
{
    public function __construct(
        private Game $game,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private JoinGameUseCase $joinGameUseCase,
        private TranslatorInterface $translator,
        private PlayerResolver $playerResolver,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerResolver->getPlayer($telegramDto->getUser());

        $this->joinGameUseCase->join($player, $telegramDto->getMessage()->getText());

        $this->telegramApi->sendMessage(
            $player->getTelegramId(),
            $this->translator->trans('You have successfully joined') . PHP_EOL . $this->translator->trans(
                'Wait until the game starts...'
            )
        );

        // TODO create PlayerHasJoined event and move logic below to the subscriber or listener. Maybe put this event into use case?
        $gameSession = $this->game->findSessionByPlayer($player);
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

        $this->telegramBot->stopProcessingCommand($player->getTelegramId());
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return false;
    }
}
