<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\AsTelegramCommand;
use App\Telegram\ConversationalCommand;
use App\Telegram\ConversationStep;
use App\Telegram\TelegramDto;
use Phptg\BotApi\TelegramBotApi;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramCommand('/join_game', supportReplyMarkup: true)]
final readonly class JoinCommand implements ConversationalCommand
{
    public const string COMMAND_NAME = '/join_game';

    public function __construct(
        private TelegramBotApi $telegramApi,
        private TranslatorInterface $translator,
        private PlayerRepository $playerRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(TelegramDto $telegramDto, ?ConversationStep $step = null): ?ConversationStep
    {
        $player = $this->playerRepository->find($telegramDto->user->id);
        $gameSession = $this->game->findSessionByPlayer($player);

        if (null !== $gameSession && $gameSession->isPlayerMaster($player)) {
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('Already playing. Would you like to finish the current one?'),
                [
                    'reply_markup' => [
                        'inline_keyboard' => [[
                            [
                                'text' => $this->translator->trans('Finish the game'),
                                'callback_data' => FinishGameCommand::COMMAND_NAME,
                            ],
                        ]],
                    ],
                ],
            );

            return null;
        }

        if (null !== $gameSession) {
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('You are in another game, do you want to leave it?'),
                [
                    'reply_markup' => [
                        'inline_keyboard' => [[
                            [
                                'text' => $this->translator->trans('Leave the game'),
                                'callback_data' => LeaveGameCommand::COMMAND_NAME,
                            ],
                        ]],
                    ],
                ],
            );

            return null;
        }

        $this->telegramBot->startProcessingCommand($player->getTelegramId(), EnterGameIdCommand::class);
        $this->telegramApi->sendMessage($player->getTelegramId(), $this->translator->trans('Enter the game ID:'));

        return null;
    }
}
