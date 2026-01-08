<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Telegram\Domain\Enum\ChatType;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsTelegramCommand
{
    /**
     * @param non-empty-list<ChatType> $chatTypes
     */
    public function __construct(
        public string $command,
        public bool $supportReplyMarkup = false,
        public array $chatTypes = [ChatType::Private],
    ) {
    }
}
