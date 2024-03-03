<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\CreateGameUseCase;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use App\Game\Infrastructure\UserResolver\PlayerResolver;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EnterThingsPerPlayerCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/start_game';

    public function __construct(
        private PlayerResolver $playerResolver,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private TranslatorInterface $translator,
        private CreateGameUseCase $createGameUseCase,
        private string $telegramBotName,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerResolver->getPlayer($telegramDto->getUser());
        $numberOfThings = new ThingsPerPlayer((int) $telegramDto->getData());

        $newGame = ($this->createGameUseCase)($player, $numberOfThings);

        $this->telegramBot->stopProcessingCommand($player->getTelegramId());

        $this->telegramApi->sendMessage(
            $player->getTelegramId(),
            implode(PHP_EOL, [
                $this->translator->trans('Join the game at this link:'),
                "https://t.me/{$this->telegramBotName}?start={$newGame->getId()}",
            ]),
        );

        $telegramResponse = $this->telegramApi->sendMessage(
            $player->getTelegramId(),
            $this->translator->trans("And then start the game as soon as you're ready"),
            [
                'reply_markup' => [
                    'inline_keyboard' => [[
                        [
                            'text' => $this->translator->trans('Start the game'),
                            'callback_data' => StartGameCommand::COMMAND_NAME,
                        ],
                    ]],
                ],
            ],
        );

        $this->telegramBot->saveEditedMessage($telegramResponse->getMessage());
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return false;
    }
}
