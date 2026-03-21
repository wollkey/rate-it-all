<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Storage;

use Phptg\BotApi\Type\Message;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class GameTelegramContext
{
    public function __construct(
        private CacheItemPoolInterface $cache,
    ) {
    }

    public function saveEditedMessage(Message $message): void
    {
        $item = $this->cache->getItem($this->getEditedMessageCacheKey($message->chat->id));
        $item->set($message->messageId);
        $item->expiresAfter(3600);
        $this->cache->save($item);
    }

    public function getEditedMessage(int $chatId): ?int
    {
        $item = $this->cache->getItem($this->getEditedMessageCacheKey($chatId));

        if (!$item->isHit()) {
            return null;
        }

        $value = $item->get();

        return is_int($value) ? $value : null;
    }

    private function getEditedMessageCacheKey(int $chatId): string
    {
        return "telegram_bot_edited_message_$chatId";
    }
}
