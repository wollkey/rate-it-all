<?php

declare(strict_types=1);

namespace App\Telegram\Domain\EntityExtractor;

use App\Telegram\Domain\Entity\TelegramRequest;

interface TelegramEntityExtractor
{
    public function extract(TelegramRequest $telegramRequest): object;
}
