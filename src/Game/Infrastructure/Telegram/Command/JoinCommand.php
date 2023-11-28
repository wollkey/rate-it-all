<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Domain\Model\Game;
use App\Game\Infrastructure\UserResolver\PlayerResolver;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class JoinCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/join_game';

    public function __construct(
        private Game $game,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
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
        $gameSession = $this->game->findSessionByPlayer($player);

        if ($gameSession !== null && $gameSession->isPlayerMaster($player)) {
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('You are already in the game. Would you like to finish the current one?'),
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

            return;
        }

        if ($gameSession !== null) {
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

            return;
        }

        $this->telegramBot->startProcessingCommand($player->getTelegramId(), EnterGameIdCommand::class);
        $this->telegramApi->sendMessage($player->getTelegramId(), $this->translator->trans('Enter the game id:'));
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->getMessage()), $telegramDto->getData() => true,
            default => false,
        };
    }
}
