<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Command;

use App\Game\Domain\Model\GameSession;
use App\Game\Domain\Repository\PlayerRepositoryInterface;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class StartGameCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/start_game';

    public function __construct(
        private GameSession $gameSession,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private PlayerRepositoryInterface $playerRepository,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $from = $telegramDto->getUser();
        $player = $this->playerRepository->find($from->getId());
        $game = $this->gameSession->continueGame($player);

        if ($game === null) {
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('First create a new game'),
                [
                    'reply_markup' => [
                        'inline_keyboard' => [[
                            [
                                'text' => $this->translator->trans('Create'),
                                'callback_data' => CreateGameCommand::COMMAND_NAME,
                            ],
                        ]],
                    ],
                ],
            );

            return;
        }

        foreach ($game->getPlayers() as $player) {
            $this->telegramBot->startProcessingCommand($player->getTelegramId(), AddThingCommand::class);
            $this->telegramApi->sendMessage($player->getTelegramId(), $this->translator->trans('Add any things that come to your mind').':');
        }
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->getMessage()), $telegramDto->getData() => true,
            default => false,
        };
    }
}
