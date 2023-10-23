<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Repository;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Model\Game;
use App\Game\Domain\Repository\GameSessionRepositoryInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

final readonly class GameSessionRepository implements GameSessionRepositoryInterface
{
    public function __construct(
        private CacheInterface $cache,
    ) {
    }

    public function findByPlayer(int $playerId): ?Game
    {
        $gameId = $this->cache->get("telegram_bot_player_$playerId", function(ItemInterface $item) {
            $item->expiresAfter(0);
            return null;
        });

        return $gameId !== null ? $this->find($gameId) : null;
    }

    public function save(Game $game): void
    {
        $cacheKey = $this->getGameCacheKey($game->getId());

        $this->cache->delete($cacheKey);
        $this->cache->get($cacheKey, function (ItemInterface $item) use ($game): Game {
            $item->expiresAfter(3600);

            return $game;
        });
    }

    public function find(string $gameId): ?Game
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
        $this->cache->get($cacheKey, fn() => $gameId);
    }

    public function delete(Game $game): void
    {
        $this->cache->delete($this->getGameCacheKey($game->getId()));
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
