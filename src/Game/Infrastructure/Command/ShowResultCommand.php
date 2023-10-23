<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Command;

use App\Game\Domain\Model\GameSession;
use App\Game\Domain\Repository\PlayerRepositoryInterface;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;

final readonly class ShowResultCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/show_result';

    public function __construct(
        private GameSession $gameSession,
        private PlayerRepositoryInterface $playerRepository,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->getUser()->getId());
        $game = $this->gameSession->continueGame($player);

        $result = $this->formatResult($game->generateResult());

        $this->telegramApi->sendMessage($player->getTelegramId(), $result);
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->getMessage()), $telegramDto->getData() => true,
            default => false,
        };
    }

    private function formatResult(array $generateResult): string
    {
        $preparedResult = '';

        foreach ($generateResult as $thing => $rating) {
            $preparedResult .= $rating . ' 👉 ' . $thing . PHP_EOL;
        }

        return $preparedResult;
    }
}
