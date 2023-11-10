<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Command;

use App\Game\Domain\Model\GameSession;
use App\Game\Domain\Repository\PlayerRepositoryInterface;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class FinishGameCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/finish_game';

    public function __construct(
        private GameSession $gameSession,
        private PlayerRepositoryInterface $playerRepository,
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
        $player = $this->playerRepository->find($telegramDto->getUser()->getId());

        if ($player === null) {
            return;
        }

        $game = $this->gameSession->continueGame($player);

        if ($game === null) {
            return;
        }

        if (!$game->isPlayerMaster($player)) {
            throw new TelegramException($this->translator->trans('Only master can finish this game. You can only leave it.'));
        }

        $this->gameSession->finishGame($game);
        $this->telegramApi->sendMessage($game->getMaster()->getTelegramId(), $this->translator->trans('The game is over!'));
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->getMessage()), $telegramDto->getData() => true,
            default => false,
        };
    }
}
