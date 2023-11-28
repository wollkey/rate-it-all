<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Domain\Model\Game;
use App\Game\Domain\Repository\PlayerRepositoryInterface;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;

final readonly class ShowResultCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/show_result';

    public function __construct(
        private Game $game,
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
        $gameSession = $this->game->continue($player);

        dump($gameSession->generateResult());
        $result = $this->formatResult($gameSession->generateResult());

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
