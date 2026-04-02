<?php

declare(strict_types=1);

namespace App\Tests\Telegram\Infrastructure\Handler;

use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use App\Telegram\Infrastructure\Handler\CommandHandlerResolver;
use App\Telegram\TelegramInput;
use App\Tests\Common\CreateTelegramInput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\DependencyInjection\ServiceLocator;

final class CommandHandlerResolverTest extends TestCase
{
    use CreateTelegramInput;

    private ConversationStorage $storage;

    protected function setUp(): void
    {
        $this->storage = new ConversationStorage(new ArrayAdapter());
    }

    public function testReturnsHandlerForTextCommand(): void
    {
        $handler = $this->createHandler();
        $resolver = $this->createResolver(['/start' => $handler]);

        $result = $resolver->resolve($this->createTextInput('/start', isCommand: true));

        self::assertSame($handler, $result);
    }

    public function testReturnsHandlerForCallbackCommand(): void
    {
        $handler = $this->createHandler();
        $resolver = $this->createResolver(['/start' => $handler]);

        $result = $resolver->resolve($this->createCallbackInput('/start'));

        self::assertSame($handler, $result);
    }

    public function testReturnsNullForPlainText(): void
    {
        $resolver = $this->createResolver([]);

        $result = $resolver->resolve($this->createTextInput('Hello'));

        self::assertNull($result);
    }

    public function testReturnsNullWhenCommandNotRegistered(): void
    {
        $resolver = $this->createResolver([]);

        $result = $resolver->resolve($this->createTextInput('/unknown', isCommand: true));

        self::assertNull($result);
    }

    public function testClearsConversationWhenCommandResolved(): void
    {
        $handler = $this->createHandler();
        $resolver = $this->createResolver(['/start' => $handler]);
        $this->storage->save(1, \stdClass::class);

        $resolver->resolve($this->createTextInput('/start', isCommand: true));

        self::assertNull($this->storage->get(1));
    }

    private function createHandler(): callable
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
    private function createResolver(array $handlers = []): CommandHandlerResolver
    {
        return new CommandHandlerResolver(
            new ServiceLocator(
                array_map(static fn ($h) => static fn () => $h, $handlers),
            ),
            $this->storage,
        );
    }
}
