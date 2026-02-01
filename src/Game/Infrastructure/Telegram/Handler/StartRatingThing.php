<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\TakeNextThingUseCase;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\TelegramInput;

final readonly class StartRatingThing
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
    public function execute(TelegramInput $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);
        ($this->startRatingThingUseCase)($player);
    }

    public function supports(TelegramInput $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->message), $telegramDto->callbackQuery?->data => true,
            default => false,
        };
    }
}
