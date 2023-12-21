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

final readonly class CreateGameCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/create_game';

    public function __construct(
        private PlayerResolver $playerResolver,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerResolver->getPlayer($telegramDto->getUser());

        $this->telegramBot->startProcessingCommand($player->getTelegramId(), EnterThingsPerPlayerCommand::class);

        $this->telegramApi->sendMessage(
            $player->getTelegramId(),
            $this->translator->trans('Enter the number of rated things per player:'),
            [
                'reply_markup' => [
                    'inline_keyboard' => [
                        array_map(static fn (int $numberOfThings): array => [
                            'text' => $numberOfThings,
                            'callback_data' => $numberOfThings,
                        ], range(Game::MIN_THINGS_PER_PLAYER, Game::MAX_THINGS_PER_PLAYER)),
                    ],
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
