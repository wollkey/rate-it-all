<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Repository;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Model\GameSession;
use App\Game\Domain\Repository\GameSessionRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class GameSessionRepository implements GameSessionRepositoryInterface
{
    private const ONE_DAY_IN_SECONDS = 3600 * 24;

    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function findByPlayer(int $playerId): ?GameSession
    {
        $gameId = $this->cache->get("telegram_bot_player_$playerId", function (ItemInterface $item) {
            $item->expiresAfter(0);

            return null;
        });

        return $gameId !== null ? $this->find($gameId) : null;
    }

    public function save(GameSession $gameSession): void
    {
        $cacheKey = $this->getGameCacheKey($gameSession->getId());

        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, function (ItemInterface $item) use ($gameSession): GameSession {
            $item->expiresAfter(self::ONE_DAY_IN_SECONDS);

            return $gameSession;
        });
    }

    public function find(string $gameId): ?GameSession
    {
        return $this->cache->get($this->getGameCacheKey($gameId), function (ItemInterface $item): null {
            $item->expiresAfter(0);

            return null;
        });
    }

    public function addPlayerToGame(Player $player, string $gameId): void
    {
        $cacheKey = $this->getPlayerCacheKey($player);
        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, fn () => $gameId);
    }

    public function delete(GameSession $gameSession): void
    {
        $this->cache->delete($this->getGameCacheKey($gameSession->getId()));
    }

    private function getGameCacheKey(string $gameId): string
    {
        return "telegram_bot_game_$gameId";
    }

    public function removePlayerFromGame(Player $player): void
    {
        $this->cache->delete($this->getPlayerCacheKey($player));
    }

    private function getPlayerCacheKey(Player $player): string
    {
        return "telegram_bot_player_{$player->getId()}";
    }
}
