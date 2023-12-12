<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\TakeNextThingUseCase;
use App\Game\Infrastructure\UserResolver\PlayerResolver;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;

final readonly class StartRatingThingCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/start_rating';

    public function __construct(
        private TakeNextThingUseCase $startRatingThingUseCase,
        private TelegramBot $telegramBot,
        private PlayerResolver $playerResolver,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerResolver->getPlayer($telegramDto->getUser());
        ($this->startRatingThingUseCase)($player);
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->getMessage()), $telegramDto->getData() => true,
            default => false,
        };
    }
}
