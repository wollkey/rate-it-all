<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Conversation;

use App\Telegram\ConversationStep;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

final readonly class ConversationStorage
{
    private const int TTL_ONE_DAY = 3600 * 24;

    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * @param class-string $handlerClass
     *
     * @throws InvalidArgumentException
     */
    public function save(int $chatId, string $handlerClass, ?string $step = null, array $data = []): void
    {
        $item = $this->cache->getItem($this->key($chatId));
        $item->set(new ConversationStep($handlerClass, $step, $data));
        $item->expiresAfter(self::TTL_ONE_DAY);
        $this->cache->save($item);
    }

    public function get(int $chatId): ?ConversationStep
    {
        $item = $this->cache->getItem($this->key($chatId));

        if (!$item->isHit()) {
            return null;
        }

        $data = $item->get();

        return $data instanceof ConversationStep ? $data : null;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function clear(int $chatId): void
    {
        $this->cache->deleteItem($this->key($chatId));
    }

    private function key(int $chatId): string
    {
        return "telegram_conversation_$chatId";
    }
}
