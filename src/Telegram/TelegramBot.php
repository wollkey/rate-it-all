<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Telegram\Dto\Message;
use App\Telegram\Enum\EntityType;
use App\Telegram\Exception\TelegramBotNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;

final readonly class TelegramBot
{
    /**
     * @param iterable<BotCommandInterface> $commands
     */
    public function __construct(
        #[TaggedIterator('app.telegram_bot.command')]
        private iterable $commands,
    ) {
    }

    public function isCommand(Message $message): bool
    {
        foreach ($message->getEntities() as $entity) {
            if ($entity->getType() === EntityType::BotCommand) {
                return true;
            }
        }

        return false;
    }

    public function executeCommand(Message $message): void
    {
        $commandName = $this->getCommand($message);

        foreach ($this->commands as $command) {
            if ($commandName === $command->getName()) {
                $command->execute($message);
                break;
            }
        }
    }

    public function getCommand(Message $message): string
    {
        $commandEntity = null;

        foreach ($message->getEntities() as $entity) {
            if ($entity->getType() === EntityType::BotCommand) {
                $commandEntity = $entity;
                break;
            }
        }

        if ($commandEntity === null) {
            throw new TelegramBotNotFoundException('Yoy must have at least one command');
        }

        return substr($message->getText(), $commandEntity->getOffset(), $commandEntity->getLength());
    }
}
