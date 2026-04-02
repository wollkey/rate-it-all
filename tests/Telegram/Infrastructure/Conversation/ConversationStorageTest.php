<?php

declare(strict_types=1);

namespace App\Tests\Telegram\Infrastructure\Conversation;

use App\Telegram\ConversationStep;
use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

final class ConversationStorageTest extends TestCase
{
    private ConversationStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new ConversationStorage(new ArrayAdapter());
    }

    public function testSaveAndGetConversationStep(): void
    {
        $this->storage->save(100, \stdClass::class);

        self::assertInstanceOf(ConversationStep::class, $this->storage->get(100));
    }

    public function testSaveConversationStepWithData(): void
    {
        $this->storage->save(100, \stdClass::class, 'Step', ['key' => 'value']);

        $step = $this->storage->get(100);
        self::assertInstanceOf(ConversationStep::class, $step);
        self::assertSame(\stdClass::class, $step->handler);
        self::assertSame('Step', $step->name);
        self::assertSame(['key' => 'value'], $step->data);
    }

    public function testGetReturnsNullIfNotSaved(): void
    {
        self::assertNull($this->storage->get(100));
    }

    public function testClearStorage(): void
    {
        $this->storage->save(100, \stdClass::class);

        $this->storage->clear(100);

        self::assertNull($this->storage->get(100));
    }
}
