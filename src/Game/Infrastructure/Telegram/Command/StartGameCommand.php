<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\AsTelegramCommand;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\Infrastructure\Http\TelegramResponder;
use App\Telegram\TelegramDto;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramCommand(self::COMMAND_NAME, [InputType::Callback])]
final readonly class StartGameCommand
{
    public const string COMMAND_NAME = '/start_game';

    public function __construct(
        private TelegramResponder $telegram,
        private PlayerRepository $playerRepository,
        private TranslatorInterface $translator,
        private GameRepository $gameRepository,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(TelegramDto $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);
        $game = $this->gameRepository->findActiveByPlayer($player);

        if ($game === null) {
            $this->sendCreateNewGameMessage($telegramDto);

            return;
        }

        if (!$game->isMaster($player)) {
            $this->telegram->answerCallbackQuery(
                callbackQueryId: $telegramDto->callbackQuery->id,
                text: $this->translator->trans('The action is not available'),
                showAlert: true,
            );

            return;
        }

        $this->telegram->answerCallbackQuery(
            callbackQueryId: $telegramDto->callbackQuery->id,
            showAlert: false,
        );

        foreach ($game->getPlayers() as $player) {
            $this->telegram->startProcessingCommand($player->getTelegramId(), AddUsernameCommand::class);
            $this->telegram->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('Add any crazy thing that came into your head:')
            );
        }
    }

    /**
     * @throws \Exception
     */
    private function sendCreateNewGameMessage(TelegramDto $telegramDto): void
    {
        $this->telegram->send(
            $telegramDto->message->chat->id,
            $this->translator->trans('Kick things off with a new game'),
            [
                'text' => $this->translator->trans('Create'),
                'callback_data' => CreateGameCommand::COMMAND_NAME,
            ],
        );
    }
}
