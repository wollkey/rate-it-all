<?php

declare(strict_types=1);

namespace App\Tests\Game\Infrastructure\Telegram\Keyboard;

use App\Game\Infrastructure\Telegram\Keyboard\RatingKeyboardFactory;
use PHPUnit\Framework\TestCase;

final class RatingKeyboardFactoryTest extends TestCase
{
    private RatingKeyboardFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new RatingKeyboardFactory();
    }

    public function testCreate(): void
    {
        $keyboard = $this->factory->create();

        $rows = $keyboard->inlineKeyboard;
        self::assertCount(2, $rows);

        self::assertCount(5, $rows[0]);
        foreach ($rows[0] as $i => $button) {
            $expected = (string) ($i + 1);
            self::assertSame($expected, $button->text);
            self::assertSame($expected, $button->callbackData);
        }

        self::assertCount(5, $rows[1]);
        foreach ($rows[1] as $i => $button) {
            $expected = (string) ($i + 6);
            self::assertSame($expected, $button->text);
            self::assertSame($expected, $button->callbackData);
        }
    }
}
