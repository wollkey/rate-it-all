<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Command;

use App\Game\Domain\Model\GameSession;
use App\Game\Domain\Repository\PlayerRepositoryInterface;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class StartRatingThingCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/start_rating';

    public function __construct(
        private GameSession $gameSession,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private PlayerRepositoryInterface $playerRepository,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $from = $telegramDto->getUser();
        $player = $this->playerRepository->find($from->getId());
        $game = $this->gameSession->continueGame($player);

        $randomThing = $game->getRandomThing();

        $game->setRatedThing($randomThing);
        $this->gameSession->save($game);

        foreach ($game->getPlayers() as $player) {
            $this->telegramBot->startProcessingCommand($player->getTelegramId(), RateTheThingCommand::class);
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('Rate the next thing: anyThing', ['anyThing' => $game->getRatedThing()->getValue()])
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
