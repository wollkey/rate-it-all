<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\StartGameUseCase;
use App\Game\Domain\Exception\ForbiddenActionException;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use App\Telegram\OnCommand;
use App\Telegram\TelegramInput;
use App\Telegram\TelegramResponder;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Psr\Cache\InvalidArgumentException;
use Symfony\Contracts\Translation\TranslatorInterface;

#[OnCommand(self::COMMAND_NAME)]
#[AsTelegramHandler(inputTypes: [InputType::Callback])]
final readonly class StartGame
{
    public const string COMMAND_NAME = '/start_game';

    public function __construct(
        private TelegramResponder $telegram,
        private PlayerRepository $playerRepository,
        private TranslatorInterface $translator,
        private GameRepository $gameRepository,
        private ConversationStorage $conversations,
        private TelegramResponder $telegramResponder,
        private StartGameUseCase $startGameUseCase,
    ) {
    }

    /**
     * @throws ForbiddenActionException
     * @throws InvalidArgumentException
     * @throws \Exception
     */
    public function __invoke(TelegramInput $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);
        $game = $this->gameRepository->findActiveByPlayer($player);

        if (!$game->isMaster($player)) {
            $this->telegram->answerCallbackQuery(
                callbackQueryId: $telegramDto->callbackQuery->id,
                text: $this->translator->trans('The action is not available'),
                showAlert: true,
            );

            return;
        }

        try {
            ($this->startGameUseCase)($player);
        } catch (GameNotFoundException) {
            $this->sendCreateNewGameMessage($telegramDto);

            return;
        }

        $this->telegram->answerCallbackQuery($telegramDto->callbackQuery->id);

        foreach ($game->getPlayers() as $player) {
            $this->conversations->save(
                chatId: $player->getTelegramId(),
                handlerClass: AddThing::class,
            );
            $this->telegramResponder->send(
                chatId: $player->getTelegramId(),
                text: $this->translator->trans('Add any crazy thing that came into your head:')
            );
        }
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
                        callbackData: CreateGame::COMMAND_NAME,
                    ),
                ],
            ]),
        );
    }
}
