<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Command;

use App\Game\Application\Dto\PlayerDto;
use App\Game\Application\UseCase\JoinGameUseCase;
use App\Game\Domain\Model\GameSession;
use App\Game\Domain\Repository\PlayerRepositoryInterface;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class EnterGameIdCommand implements BotCommandInterface
{
    public function __construct(
        private GameSession $gameSession,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private JoinGameUseCase $joinGameUseCase,
        private PlayerRepositoryInterface $playerRepository,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $user = $telegramDto->getUser();

        $playerDto = new PlayerDto((string) $user->getId());

        $this->joinGameUseCase->join($playerDto, $telegramDto->getMessage()->getText());

        $player = $this->playerRepository->find($playerDto->getId());
        $game = $this->gameSession->continueGame($player);

        $this->telegramApi->sendMessage(
            $user->getId(),
            $this->translator->trans('You have successfully joined').PHP_EOL.$this->translator->trans('Wait until the game starts')
        );
        $this->telegramApi->sendMessage(
            $game->getMaster()->getTelegramId(),
            $this->translator->trans('Player player has joined', ['player' => $user->getUsername()])
        );
        $this->telegramBot->stopProcessingCommand($user->getId());
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return false;
    }
}
