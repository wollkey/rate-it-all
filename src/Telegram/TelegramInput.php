<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Telegram\Domain\Exception\TelegramException;
use Phptg\BotApi\Type\CallbackQuery;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\User;

final readonly class TelegramInput
{
    public function __construct(
        public User $user,
        public Message $message,
        public ?CallbackQuery $callbackQuery = null,
        public ?ConversationStep $conversationStep = null,
    ) {
    }

    /**
     * @phpstan-assert-if-true CallbackQuery $this->callbackQuery
     */
    public function isCallback(): bool
    {
        return $this->callbackQuery !== null;
    }

    public function getCallbackQueryOrFail(): CallbackQuery
    {
        return $this->callbackQuery ?? throw new TelegramException('Callback query must not be empty');
    }
}
