<?php

declare(strict_types=1);

namespace App\Telegram;

final readonly class ConversationStep
{
    /**
     * @param class-string $handler
     */
    public function __construct(
        public string $handler,
        public ?string $name = null,
        public array $data = [],
    ) {
    }
}
