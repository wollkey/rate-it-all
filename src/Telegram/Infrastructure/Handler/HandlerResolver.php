<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Telegram\TelegramInput;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.telegram.handler_resolver')]
interface HandlerResolver
{
    /**
     * @return (callable(TelegramInput): void)|null
     */
    public function resolve(TelegramInput $telegramInput): ?callable;
}
