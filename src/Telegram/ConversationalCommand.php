<?php

declare(strict_types=1);

namespace App\Telegram;

interface ConversationalCommand
{
    public function __invoke(TelegramDto $telegramDto, ?ConversationStep $step = null): ?ConversationStep;
}
