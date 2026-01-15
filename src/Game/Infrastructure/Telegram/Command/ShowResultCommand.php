<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\Infrastructure\Http\TelegramResponder;
use App\Telegram\TelegramDto;

final readonly class ShowResultCommand
{
    public const string COMMAND_NAME = '/show_result';

    public function __construct(
        private PlayerRepository $playerRepository,
        private TelegramResponder $telegramApi,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->getUser()->getId());
        $gameSession = $this->game->continue($player);

        $result = $this->formatResult($gameSession->generateResult());

        $this->telegramApi->sendMessage($player->getTelegramId(), $result);
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->message), $telegramDto->callbackQuery?->data => true,
            default => false,
        };
    }

    private function formatResult(array $generateResult): string
    {
        $preparedResult = '';

        foreach ($generateResult as $thing => $rating) {
            $preparedResult .= $rating.' 👉 '.$thing.PHP_EOL;
        }

        return $preparedResult;
    }
}
