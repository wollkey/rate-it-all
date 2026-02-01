<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use App\Telegram\TelegramResponder;
use App\Telegram\TelegramInput;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramHandler(self::COMMAND_NAME, inputTypes: [InputType::Callback])]
final readonly class StartGame
{
    public const string COMMAND_NAME = '/start_game';

    public function __construct(
        private TelegramResponder $telegram,
        private PlayerRepository $playerRepository,
        private TranslatorInterface $translator,
        private GameRepository $gameRepository,
        private ConversationStorage $conversations,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(TelegramInput $telegramDto): void
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

        $this->telegram->answerCallbackQuery($telegramDto->callbackQuery->id);

        $playerIds = [];
        foreach ($game->getPlayers() as $player) {
            $this->telegram->startProcessingCommand($player->getTelegramId(), AddThingCommand::class);
            $this->telegram->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('Add any crazy thing that came into your head:')
            );
        }

        $this->conversations->saveForUsers(
            $playerIds,
            AddThingCommand::class,
        );
    }

    /**
     * @throws \Exception
     */
    private function sendCreateNewGameMessage(TelegramInput $telegramDto): void
    {
        $this->telegram->send(
            $telegramDto->message->chat->id,
            $this->translator->trans('Kick things off with a new game'),
            new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: '🎮 '.$this->translator->trans('Create'),
                        callbackData: CreateGameCommand::COMMAND_NAME,
                    ),
                ],
            ]),
        );
    }
}
