<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\JoinGameUseCase;
use App\Game\Domain\Exception\GameException;
use App\Game\Infrastructure\UserResolver\PlayerResolver;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;

final readonly class EnterGameIdCommand implements BotCommandInterface
{
    public function __construct(
        private TelegramBot $telegramBot,
        private JoinGameUseCase $joinGameUseCase,
        private PlayerResolver $playerResolver,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerResolver->getPlayer($telegramDto->getUser());

        try {
            $gameId = $this->resolveGameId($telegramDto->getMessage()->getText());
            $this->joinGameUseCase->join($player, $gameId);
        } catch (GameException $exception) {
            throw new TelegramException($exception->getMessage());
        }

        $this->telegramBot->stopProcessingCommand($player->getTelegramId());
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return str_starts_with($telegramDto->getMessage()->getText(), '/start ');
    }

    private function resolveGameId(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        if (!str_starts_with($text, '/start ')) {
            return $text;
        }

        [, $gameId] = explode(' ', $text);

        return $gameId;
    }
}
