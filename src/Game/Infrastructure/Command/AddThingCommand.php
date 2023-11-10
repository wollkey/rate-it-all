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
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AddThingCommand implements BotCommandInterface
{
    public function __construct(
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private AddThingUseCase $addThingUseCase,
        private PlayerRepositoryInterface $playerRepository,
        private GameSession $gameSession,
        private TranslatorInterface $translator
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
            $this->telegramApi->sendMessage($player->getTelegramId(), $this->translator->trans('Wait other players'));
        }

        $game = ($this->addThingUseCase)($message->getText(), new PlayerDto((string) $user->getId()));

        if ($game->playerThingLimitReached($player->getId())) {
            $this->telegramBot->stopProcessingCommand($player->getTelegramId());

            $allThingsMessage = $this->translator->trans('Great job. Wait other players...');
            $this->telegramApi->sendMessage($player->getTelegramId(), $allThingsMessage);

            if ($game->totalThingLimitReached()) {
                $this->telegramApi->sendMessage(
                    $game->getMaster()->getTelegramId(),
                    $this->translator->trans('Every players is ready.'),
                    [
                        'reply_markup' => [
                            'inline_keyboard' => [[
                                [
                                    'text' => $this->translator->trans("Let's have some fun!"),
                                    'callback_data' => StartRatingThingCommand::COMMAND_NAME,
                                ],
                            ]],
                        ],
                    ],
                );
            }

            return;
        }

        $this->telegramApi->sendMessage($player->getTelegramId(), $this->translator->trans('Great, enter the next thing:'));
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return false;
    }
}
