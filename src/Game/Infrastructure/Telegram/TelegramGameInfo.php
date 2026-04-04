<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram;

use App\Game\Application\GameInfo;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class TelegramGameInfo
{
    public function __construct(
        private GameInfo $gameInfo,
        private TranslatorInterface $translator,
    ) {
    }

    public function prettyInfo(): string
    {
        return implode(PHP_EOL, [
            $this->translator->trans($this->gameInfo->description()).PHP_EOL,
            $this->translator->trans('Gameplay rules:'),
            ...array_map(
                fn (string $rule) => '- '.$this->translator->trans($rule),
                $this->gameInfo->rules()
            ),
            '',
            $this->translator->trans('Have fun!').' 🎊',
        ]);
    }
}
