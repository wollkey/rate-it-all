<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Keyboard;

use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;

final class RatingKeyboardFactory
{
    public function create(): InlineKeyboardMarkup
    {
        return new InlineKeyboardMarkup([
            array_map(
                static fn (int $i) => new InlineKeyboardButton(text: (string) $i, callbackData: (string) $i),
                range(1, 5),
            ),
            array_map(
                static fn (int $i) => new InlineKeyboardButton(text: (string) $i, callbackData: (string) $i),
                range(6, 10),
            ),
        ]);
    }
}
