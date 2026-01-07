<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Conversation;

use App\Telegram\ConversationStep;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;

final readonly class ConversationStorage
{
    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {
    }

    /**
     * @throws InvalidArgumentException
     */
    public function save(int $chatId, string $commandClass, ConversationStep $step): void
    {
        $item = $this->cache->getItem($this->key($chatId));
        $item->set(['command' => $commandClass, 'step' => $step]);
        $item->expiresAfter(3600 * 24);
        $this->cache->save($item);
    }

    /**
     * @return array{command: class-string, step: ConversationStep}|null
     *
     * @throws InvalidArgumentException
     */
    public function get(int $chatId): ?array
    {
        $item = $this->cache->getItem($this->key($chatId));

        if (!$item->isHit()) {
            return null;
        }

        $data = $item->get();

        if (!is_array($data) || !isset($data['command'], $data['step'])) {
            return null;
        }

        return $data;
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
