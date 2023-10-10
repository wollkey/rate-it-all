<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Telegram\Dto\Message;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.telegram_bot.command')]
interface BotCommandInterface
{
    public function execute(Message $messageDto): void;

    public function getName(): string;
}
