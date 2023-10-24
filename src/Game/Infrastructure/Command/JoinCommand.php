<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Command;

use App\Game\Domain\Model\GameSession;
use App\Game\Domain\Repository\PlayerRepositoryInterface;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;

final readonly class JoinCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/join_game';

    public function __construct(
        private GameSession $gameSession,
        private PlayerRepositoryInterface $playerRepository,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $user = $telegramDto->getUser();

        $player = $this->playerRepository->find($user->getId());

        $game = $this->gameSession->continueGame($player);

        if ($game !== null) {
            $replyMessage = "You have already joined the game with id *{$game->getId()}*";
            $this->telegramApi->sendMessage($user->getId(), $replyMessage, ['parse_mode' => 'MarkdownV2']);

            return;
        }

        $this->telegramBot->startProcessingCommand($user->getId(), EnterGameIdCommand::class);
        $this->telegramApi->sendMessage($user->getId(), 'Enter the game id:');
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->getMessage()), $telegramDto->getData() => true,
            default => false,
        };
    }
}
