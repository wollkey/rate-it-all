<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\TakeNextThingUseCase;
use App\Game\Domain\Model\Game;
use App\Game\Infrastructure\UserResolver\PlayerResolver;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class StartRatingThingCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/start_rating';

    public function __construct(
        private Game $game,
        private TakeNextThingUseCase $startRatingThingUseCase,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private PlayerResolver $playerResolver,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerResolver->getPlayer($telegramDto->getUser());
        ($this->startRatingThingUseCase)($player);

        // TODO add event
        $gameSession = $this->game->findSessionByPlayer($player);
        foreach ($gameSession->getPlayers() as $player) {
            $this->telegramBot->startProcessingCommand($player->getTelegramId(), RateTheThingCommand::class);
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('Rate the next thing: anyThing', ['anyThing' => $gameSession->getCurrentRatedThing()->getThing()->getValue()])
            );
        }
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->getMessage()), $telegramDto->getData() => true,
            default => false,
        };
    }
}
