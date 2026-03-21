<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Telegram\Domain\Exception\TelegramException;
use Phptg\BotApi\TelegramBotApi;
use Phptg\BotApi\Type\InlineKeyboardMarkup;

final readonly class TelegramResponder
{
    public function __construct(
        private TelegramBotApi $api,
    ) {
    }

    public function reply(
        TelegramInput $dto,
        string $text,
        ?InlineKeyboardMarkup $keyboardMarkup = null,
        ?string $parseMode = 'markdown',
    ): void {
        $dto->isCallback()
            ? $this->replyCallback($dto, $text, $keyboardMarkup, $parseMode)
            : $this->send($dto->message->chat->id, $text, $keyboardMarkup, $parseMode);
    }

    public function send(
        int $chatId,
        string $text,
        ?InlineKeyboardMarkup $keyboardMarkup = null,
        ?string $parseMode = 'markdown',
    ): void {
        $this->api->sendMessage(
            chatId: $chatId,
            text: $text,
            parseMode: $parseMode,
            replyMarkup: $keyboardMarkup,
        );
    }

    public function editMessage(
        TelegramInput $telegramInput,
        string $text,
        ?InlineKeyboardMarkup $keyboardMarkup = null,
        ?string $parseMode = 'markdown',
    ): void {
        $this->api->editMessageText(
            text: $text,
            chatId: $telegramInput->callbackQuery?->message?->chat->id,
            messageId: $telegramInput->callbackQuery?->message?->messageId,
            parseMode: $parseMode,
            replyMarkup: $keyboardMarkup,
        );
    }

    public function replyCallback(
        TelegramInput $telegramInput,
        string $text,
        ?InlineKeyboardMarkup $keyboardMarkup = null,
        ?string $parseMode = 'markdown',
    ): void {
        $callbackQuery = $telegramInput->callbackQuery
            ?? throw new TelegramException('Callback query must not be empty');

        $this->answerCallbackQuery($callbackQuery->id);

        $this->editMessage(
            telegramInput: $telegramInput,
            text: $text,
            keyboardMarkup: $keyboardMarkup,
            parseMode: $parseMode,
        );
    }

    public function answerCallbackQuery(
        string $callbackQueryId,
        ?string $text = null,
        bool $showAlert = false,
    ): void {
        $this->api->answerCallbackQuery(
            callbackQueryId: $callbackQueryId,
            text: $text,
            showAlert: $showAlert,
        );
    }

    public function deleteMessage(TelegramInput $dto): void
    {
        $this->api->deleteMessage(
            $dto->message->chat->id,
            $dto->message->messageId,
        );
    }
}
