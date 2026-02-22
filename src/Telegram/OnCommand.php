<?php

declare(strict_types=1);

namespace App\Telegram;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class OnCommand
{
    /**
     * @param non-empty-string $command
     */
    public function __construct(
        public string $command,
    ) {
    }
}
