<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Telegram\Domain\Enum\ChatType;
use App\Telegram\Domain\Enum\InputType;

#[\Attribute(\Attribute::TARGET_CLASS)]
final readonly class AsTelegramHandler
{
    /**
     * @param non-empty-list<InputType> $inputTypes
     * @param non-empty-list<ChatType>  $chatTypes
     */
    public function __construct(
        public array $inputTypes = [InputType::Callback],
        public array $chatTypes = [ChatType::Private],
    ) {
    }
}
