<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\CreateGameUseCase;
use App\Game\Domain\Repository\PlayerRepository;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use App\Telegram\AsTelegramCommand;
use App\Telegram\ConversationalCommand;
use App\Telegram\ConversationStep;
use App\Telegram\Domain\Enum\ChatType;
use App\Telegram\TelegramDto;
use Phptg\BotApi\TelegramBotApi;
use Phptg\BotApi\Type\CallbackQuery;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Phptg\BotApi\Type\Message;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramCommand(self::COMMAND_NAME, supportReplyMarkup: true, chatTypes: [ChatType::Group, ChatType::Private])]
final readonly class CreateGameCommand implements ConversationalCommand
{
    public const string COMMAND_NAME = '/create_game';
    private const string STEP_AWAITING_THINGS_COUNT = 'awaiting_things_count';

    public function __construct(
        private PlayerRepository $playerRepository,
        private TelegramBotApi $telegram,
        private TranslatorInterface $translator,
        private CreateGameUseCase $createGameUseCase,
        private string $telegramBotName,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(TelegramDto $telegramDto, ?ConversationStep $step = null): ?ConversationStep
    {
        return match ($step?->name) {
            null => $this->askThingsCount($telegramDto),
            self::STEP_AWAITING_THINGS_COUNT => $this->createGame($telegramDto, $step),
        };
    }

    private function askThingsCount(TelegramDto $telegramDto): ConversationStep
    {
        $callbackQuery = $telegramDto->callbackQuery;
        $inlineKeyboard = new InlineKeyboardMarkup([
            array_map(static fn (int $numberOfThings): InlineKeyboardButton => new InlineKeyboardButton(
                text: (string) $numberOfThings,
                callbackData: (string) $numberOfThings,
            ), range(ThingsPerPlayer::MIN_THINGS_PER_PLAYER, ThingsPerPlayer::MAX_THINGS_PER_PLAYER)),
            [
                new InlineKeyboardButton(
                    text: '⬅️ '.$this->translator->trans('Back to menu'),
                    callbackData: RulesCommand::COMMAND_NAME,
                ),
            ],
        ]);

        match (true) {
            $callbackQuery !== null => $this->handleCallbackQuery($callbackQuery, $inlineKeyboard),
            default => $this->handleDirectMessage($telegramDto->message, $inlineKeyboard),
        };

        return new ConversationStep(self::STEP_AWAITING_THINGS_COUNT);
    }

    private function handleCallbackQuery(CallbackQuery $callbackQuery, InlineKeyboardMarkup $inlineKeyboard): void
    {
        $this->telegram->answerCallbackQuery(
            callbackQueryId: $callbackQuery->id,
            showAlert: false,
        );

        $this->telegram->editMessageText(
            text: $this->translator->trans('Enter the number of rated things per player:'),
            chatId: $callbackQuery->message->chat->id,
            messageId: $callbackQuery->message->messageId,
            replyMarkup: $inlineKeyboard,
        );
    }

    private function handleDirectMessage(Message $message, InlineKeyboardMarkup $inlineKeyboard): void
    {
        $this->telegram->sendMessage(
            chatId: $message->chat->id,
            text: $this->translator->trans('Enter the number of rated things per player:'),
            replyMarkup: $inlineKeyboard,
        );
    }

    private function createGame(TelegramDto $telegramDto, ConversationStep $step): ?ConversationStep
    {
        if ($telegramDto->callbackQuery === null) {
            return $step;
        }

        $player = $this->playerRepository->find($telegramDto->user->id);
        $numberOfThings = new ThingsPerPlayer((int) $telegramDto->callbackQuery->data);

        $newGame = ($this->createGameUseCase)($player, $numberOfThings);

        $this->telegram->sendMessage(
            $player->getTelegramId(),
            implode(PHP_EOL, [
                $this->translator->trans('Join the game at this link:'),
                "https://t.me/{$this->telegramBotName}?start={$newGame->getCode()->toRfc4122()}",
            ]),
        );

        $this->telegram->answerCallbackQuery(
            callbackQueryId: $telegramDto->callbackQuery->id,
            showAlert: false,
        );

        $this->telegram->editMessageText(
            text: $this->translator->trans("And then start the game as soon as you're ready"),
            chatId: $telegramDto->message->chat->id,
            messageId: $telegramDto->message->messageId,
            replyMarkup: new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: $this->translator->trans('Start the game'),
                        callbackData: StartGameCommand::COMMAND_NAME,
                    ),
                ],
            ]),
        );

        return null;
    }
}
