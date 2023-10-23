<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Contract;

use App\Telegram\Application\Dto\TelegramDto;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.telegram_bot.command')]
interface BotCommandInterface
{
    public function execute(TelegramDto $telegramDto): void;

    public function supports(TelegramDto $telegramDto): bool;
}
