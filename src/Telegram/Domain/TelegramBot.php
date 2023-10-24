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
        $cacheKey = $this->getCacheKey($chatId);

        $this->cache->get($cacheKey, function (ItemInterface $item) use ($fqcn): ?string {
            $item->expiresAfter(3600);

            return $fqcn;
        });
    }

    public function getProcessingCommand(int $chatId): ?BotCommandInterface
    {
        $cacheKey = $this->getCacheKey($chatId);

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
        $this->cache->delete($this->getCacheKey($chatId));
    }

    private function getCacheKey(int $chatId): string
    {
        return "telegram_bot_processing_chat_$chatId";
    }
}
