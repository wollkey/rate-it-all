<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Command;

use App\Game\Application\Dto\PlayerDto;
use App\Game\Application\UseCase\CreateGameUseCase;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;

final readonly class CreateGameCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/create_game';

    public function __construct(
        private CreateGameUseCase $useCase,
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
        $playerDto = new PlayerDto((string) $user->getId());

        $newGame = $this->useCase->newGame($playerDto);

        $this->telegramApi->sendMessage(
            $user->getId(),
            "Invite your friends with this code: `{$newGame->getId()}`",
            [
                'parse_mode' => 'markdown',
            ],
        );

        $this->telegramApi->sendMessage(
            $user->getId(),
            'And then start the game as soon as you\'re ready',
            [
                'reply_markup' => [
                    'inline_keyboard' => [[
                        [
                            'text' => 'Start the game',
                            'callback_data' => StartGameCommand::COMMAND_NAME,
                        ],
                    ]],
                ],
            ],
        );
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->getMessage()), $telegramDto->getData() => true,
            default => false,
        };
    }
}
