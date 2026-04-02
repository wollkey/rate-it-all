<?php

declare(strict_types=1);

namespace App\Tests\Telegram\Infrastructure\Handler;

use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\Infrastructure\Handler\ChainHandlerResolver;
use App\Telegram\Infrastructure\Handler\HandlerResolver;
use App\Telegram\TelegramInput;
use App\Tests\Common\CreateTelegramInput;
use PHPUnit\Framework\TestCase;

final class ChainHandlerResolverTest extends TestCase
{
    use CreateTelegramInput;

    public function testReturnsFirstMatchingHandler(): void
    {
        $handler = new TextOnlyHandler();
        $chain = $this->createChainResolver(
            $this->createDummyResolver(null),
            $this->createDummyResolver($handler),
        );

        $result = $chain->resolve($this->createTextInput('Hello'));

        self::assertSame($handler, $result);
    }

    public function testReturnsNullWhenNoResolverMatches(): void
    {
        $chain = $this->createChainResolver(
            $this->createDummyResolver(null),
            $this->createDummyResolver(null),
        );

        $result = $chain->resolve($this->createTextInput('Hello'));

        self::assertNull($result);
    }

    public function testAllowsHandlerWithoutAttribute(): void
    {
        $handler = new NoAttributeHandler();
        $chain = $this->createChainResolver($this->createDummyResolver($handler));

        $result = $chain->resolve($this->createTextInput('Hello'));

        self::assertSame($handler, $result);
    }

    public function testAllowsTextHandlerForTextInput(): void
    {
        $handler = new TextOnlyHandler();
        $chain = $this->createChainResolver($this->createDummyResolver($handler));

        $result = $chain->resolve($this->createTextInput('Hello'));

        self::assertSame($handler, $result);
    }

    public function testRejectsTextHandlerForCallbackInput(): void
    {
        $handler = new TextOnlyHandler();
        $chain = $this->createChainResolver($this->createDummyResolver($handler));

        $result = $chain->resolve($this->createCallbackInput('5'));

        self::assertNull($result);
    }

    public function testAllowsCallbackHandlerForCallbackInput(): void
    {
        $handler = new CallbackOnlyHandler();
        $chain = $this->createChainResolver($this->createDummyResolver($handler));

        $result = $chain->resolve($this->createCallbackInput('5'));

        self::assertSame($handler, $result);
    }

    public function testRejectsCallbackHandlerForTextInput(): void
    {
        $handler = new CallbackOnlyHandler();
        $chain = $this->createChainResolver($this->createDummyResolver($handler));

        $result = $chain->resolve($this->createTextInput('Hello'));

        self::assertNull($result);
    }

    public function testAllowsBothInputTypesHandler(): void
    {
        $handler = new BothInputTypesHandler();
        $chain = $this->createChainResolver($this->createDummyResolver($handler));

        self::assertSame($handler, $chain->resolve($this->createTextInput('Hello')));
        self::assertSame($handler, $chain->resolve($this->createCallbackInput('5')));
    }

    private function createChainResolver(HandlerResolver ...$resolvers): ChainHandlerResolver
    {
        return new ChainHandlerResolver($resolvers);
    }

    private function createDummyResolver(?callable $handler): HandlerResolver
    {
        return new readonly class($handler) implements HandlerResolver {
            public function __construct(private mixed $handler)
            {
            }

            public function resolve(TelegramInput $telegramInput): ?callable
            {
                /** @var (callable(TelegramInput): void)|null */
                return $this->handler;
            }
        };
    }
}

#[AsTelegramHandler([InputType::Text])]
final class TextOnlyHandler
{
    public function __invoke(TelegramInput $input): void
    {
    }
}

#[AsTelegramHandler([InputType::Callback])]
final class CallbackOnlyHandler
{
    public function __invoke(TelegramInput $input): void
    {
    }
}

#[AsTelegramHandler([InputType::Text, InputType::Callback])]
final class BothInputTypesHandler
{
    public function __invoke(TelegramInput $input): void
    {
    }
}

final class NoAttributeHandler
{
    public function __invoke(TelegramInput $input): void
    {
    }
}
