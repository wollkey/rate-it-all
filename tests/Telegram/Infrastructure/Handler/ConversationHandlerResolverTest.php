<?php

declare(strict_types=1);

namespace App\Tests\Telegram\Infrastructure\Handler;

use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use App\Telegram\Infrastructure\Handler\ConversationHandlerResolver;
use App\Telegram\TelegramInput;
use App\Tests\Common\CreateTelegramInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class ConversationHandlerResolverTest extends TestCase
{
    use CreateTelegramInput;

    private ConversationStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new ConversationStorage(new ArrayAdapter());
    }

    public function testReturnsHandlerFromConversationStep(): void
    {
        $handler = $this->createHandler();
        $resolver = $this->createResolver([$handler::class => $handler]);
        $this->storage->save(1, $handler::class);

        $result = $resolver->resolve($this->createTextInput('Hello'));

        self::assertSame($handler, $result);
    }

    public function testReturnsNullWhenNoConversationStep(): void
    {
        $resolver = $this->createResolver([]);

        $result = $resolver->resolve($this->createTextInput('Hello'));

        self::assertNull($result);
    }

    public function testReturnsNullAndClearsWhenHandlerNotRegistered(): void
    {
        $resolver = $this->createResolver([]);
        $this->storage->save(1, \stdClass::class);

        $result = $resolver->resolve($this->createTextInput('Hello'));

        self::assertNull($result);
        self::assertNull($this->storage->get(1));
    }

    public function testClearsConversationWhenHandlerResolved(): void
    {
        $handler = $this->createHandler();
        $resolver = $this->createResolver([$handler::class => $handler]);
        $this->storage->save(1, $handler::class);

        $resolver->resolve($this->createTextInput('Hello'));

        self::assertNull($this->storage->get(1));
    }

    /**
     * @return object&callable(TelegramInput): void
     */
    private function createHandler(): object
    {
        return new class {
            public function __invoke(TelegramInput $telegramInput): void
            {
            }
        };
    }

    /**
     * @param array<string, callable(TelegramInput): void> $handlers
     */
    private function createResolver(array $handlers): ConversationHandlerResolver
    {
        return new ConversationHandlerResolver(
            new ServiceLocator(
                array_map(static fn ($h) => static fn () => $h, $handlers),
            ),
            $this->storage,
        );
    }
}
