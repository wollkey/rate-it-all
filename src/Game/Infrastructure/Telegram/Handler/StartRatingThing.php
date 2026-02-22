<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\StartRateThingsUseCase;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\AsTelegramHandler;
use App\Telegram\OnCommand;
use App\Telegram\TelegramInput;
use App\Telegram\TelegramResponder;

#[OnCommand(self::COMMAND_NAME)]
#[AsTelegramHandler]
final readonly class StartRatingThing
{
    public const string COMMAND_NAME = '/start_rating';

    public function __construct(
        private StartRateThingsUseCase $startRateThingsUseCase,
        private PlayerRepository $playerRepository,
        private TelegramResponder $telegramResponder,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(TelegramInput $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);
        ($this->startRateThingsUseCase)($player);
        $this->telegramResponder->deleteMessage($telegramDto);
    }
}
