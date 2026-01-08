<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\JoinGameUseCase;
use App\Game\Domain\Exception\GameException;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\TelegramDto;

final readonly class EnterGameIdCommand
{
    public function __construct(
        private JoinGameUseCase $joinGameUseCase,
        private PlayerRepository $playerRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);

        try {
            $gameId = $this->resolveGameId($telegramDto->getMessage()->getText());
            $this->joinGameUseCase->join($player, $gameId);
        } catch (GameException $exception) {
            throw new TelegramException($exception->getMessage());
        }
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return str_starts_with($telegramDto->message->text, '/start ');
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
