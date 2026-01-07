<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\TakeNextThingUseCase;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\TelegramDto;

final readonly class StartRatingThingCommand
{
    public const string COMMAND_NAME = '/start_rating';

    public function __construct(
        private TakeNextThingUseCase $startRatingThingUseCase,
        private PlayerRepository $playerRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);
        ($this->startRatingThingUseCase)($player);
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->message), $telegramDto->callbackQuery?->data => true,
            default => false,
        };
    }
}
