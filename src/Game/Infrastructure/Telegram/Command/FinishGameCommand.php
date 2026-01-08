<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\TelegramDto;
use Phptg\BotApi\TelegramBotApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class FinishGameCommand
{
    public const string COMMAND_NAME = '/finish_game';

    public function __construct(
        private PlayerRepository $playerRepository,
        private TelegramBotApi $telegramApi,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);

        if ($player === null) {
            return;
        }

        $gameSession = $this->game->continue($player);

        if (!$gameSession->isPlayerMaster($player)) {
            throw new TelegramException($this->translator->trans('Only the game master can end this party. You can gracefully exit, though.'));
        }

        $this->game->finishGameSession($gameSession);
        $this->telegramApi->sendMessage($gameSession->getMaster()->getTelegramId(), $this->translator->trans('The game is over!'));
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->message), $telegramDto->callbackQuery?->data => true,
            default => false,
        };
    }
}
