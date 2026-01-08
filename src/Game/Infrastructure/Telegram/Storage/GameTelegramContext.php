<?php

declare(strict_types=1);
namespace App\Game\Infrastructure\Telegram\Storage;

use Phptg\BotApi\Type\Message;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class GameTelegramContext
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function saveEditedMessage(Message $message): void
    {
        $chatId = $message->chat->id;

        $this->cache->delete($this->getEditedMessageCacheKey($chatId));
        $this->cache->get($this->getEditedMessageCacheKey($chatId), function (ItemInterface $item) use ($message) {
            $item->set($message);
            $item->expiresAfter(3600);

            return $message->messageId;
        });
    }

    public function getEditedMessage(int $chatId): ?int
    {
        return $this->cache->get($this->getEditedMessageCacheKey($chatId), function (ItemInterface $item) {
            $item->expiresAfter(3600);

            return $item->get();
        });
    }

    public function removeEditedMessage(int $chatId): void
    {
        $this->cache->delete($this->getEditedMessageCacheKey($chatId));
    }

    private function getEditedMessageCacheKey(int $chatId): string
    {
        return "telegram_bot_edited_message_$chatId";
    }
}