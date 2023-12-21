<?php

declare(strict_types=1);

namespace App\Telegram\Domain;

use App\Telegram\Domain\Entity\Message;
use App\Telegram\Domain\Enum\EntityType;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedLocator;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class TelegramBot
{
    /**
     * @param ContainerInterface<BotCommandInterface> $commands
     */
    public function __construct(
        #[TaggedLocator('app.telegram_bot.command')]
        private ContainerInterface $commands,
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

    public function getMessageCommand(Message $message): ?string
    {
        if ($message->getText() === null) {
            return null;
        }

        $commandEntity = null;

        foreach ($message->getEntities() as $entity) {
            if ($entity->getType() === EntityType::BotCommand) {
                $commandEntity = $entity;
                break;
            }
        }

        if ($commandEntity === null) {
            return null;
        }

        return substr($message->getText(), $commandEntity->getOffset(), $commandEntity->getLength());
    }

    /**
     * @param class-string $fqcn
     */
    public function startProcessingCommand(int $chatId, string $fqcn): void
    {
        $cacheKey = $this->getProcessingCommandCacheKey($chatId);

        $this->cache->get($cacheKey, function (ItemInterface $item) use ($fqcn): ?string {
            $item->expiresAfter(3600 * 24);

            return $fqcn;
        });
    }

    public function getProcessingCommand(int $chatId): ?BotCommandInterface
    {
        $cacheKey = $this->getProcessingCommandCacheKey($chatId);

        $fqcn = $this->cache->get($cacheKey, function (ItemInterface $item): null {
            $item->expiresAfter(0);

            return null;
        });

        return $fqcn !== null && $this->commands->has($fqcn)
            ? $this->commands->get($fqcn)
            : null;
    }

    public function stopProcessingCommand(int $chatId): void
    {
        $this->cache->delete($this->getProcessingCommandCacheKey($chatId));
    }

    public function getEditedMessage(int $chatId): ?Message
    {
        return $this->cache->get($this->getEditedMessageCacheKey($chatId), function (ItemInterface $item) {
            $item->expiresAfter(3600);

            return $item->get();
        });
    }

    public function saveEditedMessage(Message $message): void
    {
        $chatId = $message->getChat()->getId();

        $this->cache->get($this->getEditedMessageCacheKey($chatId), function (ItemInterface $item) use ($message) {
            $item->set($message);
            $item->expiresAfter(3600);

            return $message;
        });
    }

    public function removeEditedMessage(int $chatId): void
    {
        $this->cache->delete($this->getEditedMessageCacheKey($chatId));
    }

    private function getProcessingCommandCacheKey(int $chatId): string
    {
        return "telegram_bot_processing_command_$chatId";
    }

    private function getEditedMessageCacheKey(int $chatId): string
    {
        return "telegram_bot_edited_message_$chatId";
    }
}
