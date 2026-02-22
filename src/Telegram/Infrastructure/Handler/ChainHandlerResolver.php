<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Enum\ChatType;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\TelegramInput;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

final readonly class ChainHandlerResolver implements HandlerResolver
{
    /**
     * @param iterable<HandlerResolver> $resolvers
     */
    public function __construct(
        #[AutowireIterator('app.telegram.handler_resolver')]
        private iterable $resolvers,
    ) {
    }

    /**
     * @return callable(TelegramInput): void|null
     */
    public function resolve(TelegramInput $telegramInput): ?callable
    {
        foreach ($this->resolvers as $resolver) {
            $handler = $resolver->resolve($telegramInput);
            if ($handler !== null && $this->allows($handler, $telegramInput)) {
                return $handler;
            }
        }

        return null;
    }

    private function allows(callable $handler, TelegramInput $telegramInput): bool
    {
        $reflection = new \ReflectionClass($handler);
        $attributes = $reflection->getAttributes(AsTelegramHandler::class);

        if ($attributes === []) {
            return true;
        }

        /** @var AsTelegramHandler $attribute */
        $attribute = $attributes[0]->newInstance();

        $chatType = ChatType::tryFrom($telegramInput->message->chat->type);
        if ($chatType !== null && !in_array($chatType, $attribute->chatTypes)) {
            return false;
        }

        $inputType = $telegramInput->isCallback() ? InputType::Callback : InputType::Text;
        if (!in_array($inputType, $attribute->inputTypes, true)) {
            return false;
        }

        return true;
    }
}
