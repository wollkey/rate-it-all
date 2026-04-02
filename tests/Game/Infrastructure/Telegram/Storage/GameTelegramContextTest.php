<?php

declare(strict_types=1);

namespace App\Tests\Game\Infrastructure\Telegram\Storage;

use App\Game\Infrastructure\Telegram\Storage\GameTelegramContext;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class GameTelegramContextTest extends TestCase
{
    private GameTelegramContext $context;

    protected function setUp(): void
    {
        $this->context = new GameTelegramContext(new ArrayAdapter());
    }

    public function testEditedMessageSaved(): void
    {
        $this->context->saveEditedMessage(100, 42);

        self::assertSame(42, $this->context->getEditedMessage(100));
    }

    public function testGetEditedMessageReturnsNullIfNotSaved(): void
    {
        self::assertNull($this->context->getEditedMessage(100));
    }

    public function testGetEditedMessageReturnsNullForDifferentChat(): void
    {
        $this->context->saveEditedMessage(100, 42);

        self::assertNull($this->context->getEditedMessage(200));
    }
}
