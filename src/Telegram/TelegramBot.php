<?php

declare(strict_types=1);

namespace App\Telegram;

use App\Telegram\Dto\Message;
use App\Telegram\Enum\EntityType;
use App\Telegram\Exception\TelegramBotNotFoundException;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class TelegramBot
{
    /**
     * @param iterable<BotCommandInterface> $commands
     */
    public function __construct(
        #[TaggedIterator('app.telegram_bot.command')]
        private iterable $commands,
        private CacheInterface $cache,
    ) {
    }

    public function isMessageCommand(Message $message): bool
    {
        foreach ($message->getEntities() as $entity) {
            if ($entity->getType() === EntityType::BotCommand) {
                return true;
            }
        }

        return false;
    }

    public function executeCommand(string $commandName, Message $message): void
    {
        foreach ($this->commands as $command) {
            if ($commandName === $command->getName()) {
                $command->execute($message);
                return;
            }
        }

        throw new TelegramBotNotFoundException("Command $commandName not found");
    }

    public function getMessageCommand(Message $message): string
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

    public function startProcessingCommand(int $chatId, string $command): void
    {
        $cacheKey = $this->getCacheKey($chatId);

        $this->cache->get($cacheKey, function (ItemInterface $item) use ($command): ?string {
            $item->expiresAfter(3600);

            return $command;
        });
    }

    public function getProcessingCommand(int $chatId): ?string
    {
        $cacheKey = $this->getCacheKey($chatId);

        return $this->cache->get($cacheKey, function (ItemInterface $item): null {
            $item->expiresAfter(0);

            return null;
        });
    }

    public function stopProcessingCommand(int $chatId): void
    {
        $this->cache->delete($this->getCacheKey($chatId));
    }

    private function getCacheKey(int $chatId): string
    {
        return "telegram_game_bot_chat_$chatId";
    }
}
