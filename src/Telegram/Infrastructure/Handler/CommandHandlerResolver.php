<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Handler;

use App\Telegram\Domain\Enum\EntityType;
use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use App\Telegram\TelegramInput;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

#[AsTaggedItem(priority: 30)]
final readonly class CommandHandlerResolver implements HandlerResolver
{
    /**
     * @param ContainerInterface<callable(TelegramInput):void> $handlers
     */
    public function __construct(
        #[AutowireLocator('app.telegram.command_handler', indexAttribute: 'key')]
        private ContainerInterface $handlers,
        private ConversationStorage $conversation,
    ) {
    }

    public function resolve(TelegramInput $telegramInput): ?callable
    {
        $command = $this->extractCommand($telegramInput);

        if ($command === null || !$this->handlers->has($command)) {
            return null;
        }

        $this->conversation->clear($telegramInput->message->chat->id);

        /* @var callable(TelegramInput): void */
        return $this->handlers->get($command);
    }

    private function extractCommand(TelegramInput $telegramInput): ?string
    {
        if ($telegramInput->isCallback()) {
            $command = $telegramInput->callbackQuery->data;

            if ($command === null) {
                return null;
            }

            $trimmedCommand = trim($command);

            return str_starts_with($trimmedCommand, '/') ? $trimmedCommand : null;
        }

        $command = $telegramInput->message->text;

        if ($command === null) {
            return null;
        }

        foreach ((array) $telegramInput->message->entities as $entity) {
            if ($entity->type === EntityType::BotCommand->value) {
                return substr($command, $entity->offset, $entity->length);
            }
        }

        return null;
    }
}
