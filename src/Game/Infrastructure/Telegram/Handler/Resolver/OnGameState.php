<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler\Resolver;

use App\Game\Domain\GameState;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class OnGameState
{
    public function __construct(
        public GameState $state,
    ) {
    }
}
