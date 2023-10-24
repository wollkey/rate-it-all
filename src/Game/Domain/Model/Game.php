<?php

declare(strict_types=1);

namespace App\Game\Domain\Model;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameException;
use Ramsey\Uuid\UuidInterface;

final class Game
{
    /**
     * @param Player[] $players
     * @param array<int, string[]> $playerThings
     * @param array $thingRatings
     * TODO add param types
     */
    public function __construct(
        private readonly string $id,
        private readonly Player $master,
        private readonly int $thingPerPlayer,
        private array $players = [],
        private array $playerThings = [],
        private array $thingRatings = [],
        private ?Thing $ratedThing = null,
    ) {
        $this->addPlayer($this->master);
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getMaster(): Player
    {
        return $this->master;
    }

    public function addPlayer(Player $player): self
    {
        if (array_key_exists($player->getId(), $this->players)) {
            throw new GameException('Player is already in the game');
        }

        $this->players[$player->getId()] = $player;

        return $this;
    }

    public function removePlayer(Player $player): void
    {
        unset($this->players[$player->getId()]);
    }

    /**
     * @return Player[]
     */
    public function getPlayers(): array
    {
        return $this->players;
    }

    public function addThing(Player $player, Thing $thing): self
    {
        $this->thingRatings[$thing->getValue()] = [];
        $this->playerThings[$player->getId()][] = $thing;

        return $this;
    }

    /**
     * @param int<1, 10> $rating
     */
    public function rateThing(Thing $thing, int $playerId, int $rating): void
    {
        if ($rating < 1 || $rating > 10) {
            throw new GameException("You must use only from 1 to 10 numbers");
        }

        if (!$this->thingExists($thing)) {
            throw new GameException("This thing {$thing->getValue()} not in the list");
        }

        $this->thingRatings[$thing->getValue()][$playerId] = $rating;
    }

    public function thingExists(Thing $thing): bool
    {
        return array_key_exists($thing->getValue(), $this->thingRatings);
    }

    public function playerThingLimitReached(int $playerId): bool
    {
        return
            array_key_exists($playerId, $this->playerThings)
            && count($this->playerThings[$playerId]) >= $this->thingPerPlayer;
    }

    public function totalThingLimitReached(): bool
    {
        return count($this->thingRatings) >= count($this->players) * $this->thingPerPlayer;
    }

    public function getRandomThing(): ?Thing
    {
        $unratedThings = $this->getUnratedThings();

        if (empty($unratedThings)) {
            return null;
        }

        return $unratedThings[array_rand($unratedThings)];
    }

    public function getUnratedThings(): array
    {
        $unratedThings = [];

        foreach ($this->thingRatings as $thing => $playerRatings) {
            if (empty($playerRatings)) {
                $unratedThings[] = new Thing($thing);
            }
        }

        return $unratedThings;
    }

    public function getRatedThing(): ?Thing
    {
        return $this->ratedThing;
    }

    public function setRatedThing(Thing $ratedThing): void
    {
        $this->ratedThing = $ratedThing;
    }

    public function alreadyRated(Thing $ratedThing, int $playerId): bool
    {
        return array_key_exists($playerId, $this->thingRatings[$ratedThing->getValue()]);
    }

    public function isThingFullyRated(Thing $ratedThing): bool
    {
        return count($this->thingRatings[$ratedThing->getValue()]) === count($this->players);
    }

    public function generateResult(): array
    {
        $thingsRatings = array_map(
            fn ($rating) => (int) (array_sum($rating) / count($this->players)),
            $this->thingRatings,
        );

        asort($thingsRatings);

        return $thingsRatings;
    }

    public function isPlayerMaster(?Player $player): bool
    {
        return $this->getMaster()->getId() === $player->getId();
    }
}
