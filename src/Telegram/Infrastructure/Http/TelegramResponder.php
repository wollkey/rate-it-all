<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Http;

use App\Telegram\TelegramDto;
use Phptg\BotApi\TelegramBotApi;
use Phptg\BotApi\Type\InlineKeyboardMarkup;

final readonly class TelegramResponder {
    public function __construct(
        private TelegramBotApi $api,
    ) {
    }

    public function reply(
        TelegramDto $dto,
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

    public function replyCallback(
        TelegramDto $dto,
        string $text,
        ?InlineKeyboardMarkup $keyboardMarkup = null,
        ?string $parseMode = 'markdown',
    ): void
    {
        $this->api->answerCallbackQuery(
            callbackQueryId: $dto->callbackQuery->id,
            showAlert: false,
        );

        $this->api->editMessageText(
            text: $text,
            chatId: $dto->callbackQuery->message->chat->id,
            messageId: $dto->callbackQuery->message->messageId,
            parseMode: $parseMode,
            replyMarkup: $keyboardMarkup,
        );
    }
}
