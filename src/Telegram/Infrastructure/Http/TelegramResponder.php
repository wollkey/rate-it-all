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
        ?string $parseMode = 'MarkdownV2', // TODO узнать в чём разница между markdown и MarkdownV2
    ): void {
        $dto->isCallback()
            ? $this->replyCallback($dto, $text, $parseMode, $keyboardMarkup)
            : $this->send($dto->message->chat->id, $text, $parseMode, $keyboardMarkup);
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