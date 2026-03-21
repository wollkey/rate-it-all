<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use App\Telegram\TelegramInput;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

#[AsTaggedItem(priority: 20)]
final readonly class ConversationHandlerResolver implements HandlerResolver
{
    public function __construct(
        #[AutowireLocator('app.telegram.handler')]
        private ContainerInterface $handlers,
        private ConversationStorage $conversation,
    ) {
    }

    public function resolve(TelegramInput $telegramInput): ?callable
    {
        $step = $this->conversation->get($telegramInput->message->chat->id);

        if ($step === null) {
            return null;
        }

        if (!$this->handlers->has($step->handler)) {
            $this->conversation->clear($telegramInput->message->chat->id);

            return null;
        }

        $this->conversation->clear($telegramInput->message->chat->id);

        /** @var callable(TelegramInput): void */
        return $this->handlers->get($step->handler);
    }
}
