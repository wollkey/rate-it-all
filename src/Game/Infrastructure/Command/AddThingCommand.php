<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Command;

use App\Game\Application\Dto\PlayerDto;
use App\Game\Application\UseCase\AddThingUseCase;
use App\Game\Domain\Model\GameSession;
use App\Game\Domain\Repository\PlayerRepositoryInterface;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;

final readonly class AddThingCommand implements BotCommandInterface
{
    public function __construct(
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private AddThingUseCase $addThingUseCase,
        private PlayerRepositoryInterface $playerRepository,
        private GameSession $gameSession,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $user = $telegramDto->getUser();
        $message = $telegramDto->getMessage();

        $player = $this->playerRepository->find($user->getId());
        $game = $this->gameSession->continueGame($player);

        if ($game->playerThingLimitReached($player->getId())) {
            $this->telegramApi->sendMessage($player->getTelegramId(), 'Wait other players');
        }

        $game = ($this->addThingUseCase)($message->getText(), new PlayerDto((string) $user->getId()));

        if ($game->playerThingLimitReached($player->getId())) {
            $this->telegramBot->stopProcessingCommand($player->getTelegramId());

            $allThingsMessage = 'Great job. Wait other players...';
            $this->telegramApi->sendMessage($player->getTelegramId(), $allThingsMessage);

            if ($game->totalThingLimitReached()) {
                $readyMessage = 'Every players is ready.';
                $this->telegramApi->sendMessage(
                    $game->getMaster()->getTelegramId(),
                    $readyMessage,
                    [
                        'reply_markup' => [
                            'inline_keyboard' => [[
                                [
                                    'text' => "Let's have some fun!",
                                    'callback_data' => StartRatingThingCommand::COMMAND_NAME,
                                ],
                            ]],
                        ],
                    ],
                );
            }

            return;
        }

        $this->telegramApi->sendMessage($player->getTelegramId(), 'Great, enter the next thing:');
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return false;
    }
}
