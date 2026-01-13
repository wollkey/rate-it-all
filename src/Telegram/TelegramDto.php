<?php

declare(strict_types=1);

namespace App\Telegram;

use Phptg\BotApi\Type\CallbackQuery;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\User;

// TODO rename to TelegramInput
final readonly class TelegramDto
{
    public function __construct(
        public User $user,
        public Message $message,
        public ?CallbackQuery $callbackQuery = null,
    ) {
    }

    public function isCallback(): bool
    {
        return $this->callbackQuery !== null;
    }
}
