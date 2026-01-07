<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Domain\GameInfo;
use App\Telegram\AsTelegramCommand;
use App\Telegram\TelegramDto;
use Phptg\BotApi\TelegramBotApi;
use Phptg\BotApi\Type\CallbackQuery;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Phptg\BotApi\Type\Message;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramCommand('/rules', supportReplyMarkup: true)]
final readonly class RulesCommand
{
    public const string COMMAND_NAME = '/rules';

    public function __construct(
        private TelegramBotApi $telegram,
        private TranslatorInterface $translator,
        private GameInfo $gameInfo,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(TelegramDto $telegramDto): void
    {
        $callbackQuery = $telegramDto->callbackQuery;
        $inlineKeyboard = new InlineKeyboardMarkup([
            [
                new InlineKeyboardButton(
                    text: '🎮 '.$this->translator->trans('Create'),
                    callbackData: CreateGameCommand::COMMAND_NAME,
                ),
                new InlineKeyboardButton(
                    text: '🔗 '.$this->translator->trans('Join'),
                    callbackData: JoinCommand::COMMAND_NAME,
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
            text: $this->gameInfo->prettyInfo(),
            chatId: $callbackQuery->message->chat->id,
            messageId: $callbackQuery->message->messageId,
            parseMode: 'markdown',
            replyMarkup: $inlineKeyboard,
        );
    }

    private function handleDirectMessage(Message $message, InlineKeyboardMarkup $inlineKeyboard): void
    {
        $this->telegram->sendMessage(
            chatId: $message->chat->id,
            text: $this->gameInfo->prettyInfo(),
            parseMode: 'markdown',
            replyMarkup: $inlineKeyboard,
        );
    }
}
