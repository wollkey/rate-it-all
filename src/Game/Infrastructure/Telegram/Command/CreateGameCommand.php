<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Domain\Repository\PlayerRepository;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use App\Telegram\AsTelegramCommand;
use App\Telegram\ConversationalCommand;
use App\Telegram\ConversationStep;
use App\Telegram\TelegramDto;
use Phptg\BotApi\TelegramBotApi;
use Phptg\BotApi\Type\CallbackQuery;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Phptg\BotApi\Type\Message;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramCommand(CreateGameCommand::COMMAND_NAME)]
final readonly class CreateGameCommand implements ConversationalCommand
{
    public const string COMMAND_NAME = '/create_game';

    public function __construct(
        private PlayerRepository $playerRepository,
        private TelegramBotApi $telegram,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(TelegramDto $telegramDto, ?ConversationStep $step = null): ?ConversationStep
    {
        $player = $this->playerRepository->find($telegramDto->user->id);

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
            null !== $callbackQuery => $this->handleCallbackQuery($callbackQuery, $inlineKeyboard),
            default => $this->handleDirectMessage($telegramDto->message, $inlineKeyboard),
        };
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
}
