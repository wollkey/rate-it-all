<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\CreateGameUseCase;
use App\Game\Domain\Entity\Player;
use App\Game\Domain\Game;
use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\Repository\PlayerRepository;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use App\Game\Infrastructure\Telegram\Storage\GameTelegramContext;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use App\Telegram\OnCommand;
use App\Telegram\TelegramInput;
use App\Telegram\TelegramResponder;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Contracts\Translation\TranslatorInterface;

#[OnCommand(command: self::COMMAND_NAME)]
#[AsTelegramHandler(inputTypes: [InputType::Text, InputType::Callback])]
final readonly class CreateGame
{
    public const string COMMAND_NAME = '/create_game';
    private const string STEP_AWAITING_THINGS_COUNT = 'awaiting_things_count';

    public function __construct(
        private ConversationStorage $conversation,
        private CreateGameUseCase $createGameUseCase,
        private GameRepository $gameRepository,
        private GameTelegramContext $gameTelegramContext,
        private PlayerRepository $playerRepository,
        private TelegramResponder $telegramResponder,
        private TranslatorInterface $translator,
        private string $telegramBotName,
    ) {
    }

    public function __invoke(TelegramInput $telegramInput): void
    {
        $player = $this->playerRepository->find($telegramInput->user->id);
        $game = $this->gameRepository->findActiveByPlayer($player);

        if ($game !== null) {
            $this->sendPlayerAlreadyInGameMessage($telegramInput, $game, $player);

            return;
        }

        match ($telegramInput->conversationStep?->name) {
            null => $this->askThingsCount($telegramInput),
            self::STEP_AWAITING_THINGS_COUNT => $this->createGame($telegramInput, $player),
        };
    }

    private function askThingsCount(TelegramInput $telegramInput): void
    {
        $inlineKeyboard = new InlineKeyboardMarkup([
            array_map(static fn (int $numberOfThings): InlineKeyboardButton => new InlineKeyboardButton(
                text: (string) $numberOfThings,
                callbackData: (string) $numberOfThings,
            ), range(ThingsPerPlayer::MIN_THINGS_PER_PLAYER, ThingsPerPlayer::MAX_THINGS_PER_PLAYER)),
            [
                new InlineKeyboardButton(
                    text: '⬅️ '.$this->translator->trans('Back to menu'),
                    callbackData: ShowRules::COMMAND_NAME,
                ),
            ],
        ]);

        $this->telegramResponder->reply(
            $telegramInput,
            $this->translator->trans('Enter the number of rated things per player:'),
            $inlineKeyboard,
        );

        $this->conversation->save(
            $telegramInput->message->chat->id,
            self::class,
            self::STEP_AWAITING_THINGS_COUNT,
        );
    }

    private function createGame(TelegramInput $telegramInput, Player $player): void
    {
        if (!$telegramInput->isCallback()) {
            return;
        }

        $this->gameTelegramContext->saveEditedMessage($telegramInput->message);

        $numberOfThings = new ThingsPerPlayer((int) $telegramInput->callbackQuery->data);

        $newGame = ($this->createGameUseCase)($player, $numberOfThings);

        $joinLink = "https://t.me/{$this->telegramBotName}?start={$newGame->getCode()->toRfc4122()}";

        $this->telegramResponder->replyCallback(
            $telegramInput,
            'Отправьте ссылку друзьям, чтобы они присоединились',
            new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: '📤 Поделиться с друзьями',
                        url: 'https://t.me/share/url?url='.urlencode($joinLink).'&text=Присоединяйся к игре!',
                    ),
                ],
            ]),
        );
    }

    private function sendPlayerAlreadyInGameMessage(TelegramInput $telegramInput, Game $game, Player $player): void
    {
        if ($game->isMaster($player)) {
            $message = $this->translator->trans('Already playing. Would you like to finish the current one?');
            $keyboard = new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: '💀'.$this->translator->trans('Finish the game'),
                        callbackData: FinishGame::COMMAND_NAME,
                    ),
                ],
            ]);
        } else {
            $message = $this->translator->trans('You are in another game, do you want to leave it?');
            $keyboard = new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: '💀'.$this->translator->trans('Leave the game'),
                        callbackData: LeaveGame::COMMAND_NAME,
                    ),
                ],
            ]);
        }

        $this->telegramResponder->reply($telegramInput, $message, $keyboard);
    }
}
