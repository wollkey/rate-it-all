<?php

declare(strict_types=1);

namespace App\Game\Domain\Model;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\PlayerAlreadyInGameException;
use App\Game\Domain\Exception\ThingNotInTheListException;
use App\Game\Domain\ValueObject\Rating;
use App\Game\Domain\ValueObject\Thing;
use App\Game\Domain\ValueObject\ThingsPerPlayer;

final class GameSession
{
    /**
     * @param Player[]                  $players
     * @param array<int, Thing[]>       $playerThings
     * @param array<string, RatedThing> $ratedThings
     *
     * @throws PlayerAlreadyInGameException
     */
    public function __construct(
        private readonly string $id,
        private readonly Player $master,
        private readonly ThingsPerPlayer $thingPerPlayer,
        private array $players = [],
        private array $playerThings = [],
        private array $ratedThings = [],
        private ?RatedThing $currentRatedThing = null,
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

    public function getThingPerPlayer(): ThingsPerPlayer
    {
        return $this->thingPerPlayer;
    }

    /**
     * @throws PlayerAlreadyInGameException
     */
    public function addPlayer(Player $player): self
    {
        if (array_key_exists($player->getId(), $this->players)) {
            throw new PlayerAlreadyInGameException('Player is already in the game');
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
        $this->ratedThings[$thing->getHash()] = new RatedThing($thing);
        $this->playerThings[$player->getId()][] = $thing;

        return $this;
    }

    /**
     * @throws ThingNotInTheListException
     */
    public function rateThing(Thing $thing, Player $player, Rating $rating): void
    {
        if (!$this->thingExists($thing)) {
            throw new ThingNotInTheListException("This thing {$thing->getValue()} not in the list");
        }

        $ratedThing = $this->ratedThings[$thing->getHash()];
        $ratedThing->rateThing($player, $rating);
    }

    public function thingExists(Thing $thing): bool
    {
        return array_key_exists($thing->getHash(), $this->ratedThings);
    }

    public function playerThingLimitReached(int $playerId): bool
    {
        return
            array_key_exists($playerId, $this->playerThings)
            && count($this->playerThings[$playerId]) >= $this->thingPerPlayer->getValue();
    }

    public function totalThingLimitReached(): bool
    {
        return count($this->ratedThings) >= count($this->players) * $this->thingPerPlayer->getValue();
    }

    public function getRandomUnratedThing(): ?Thing
    {
        $unratedThings = $this->getUnratedThings();

        return !empty($unratedThings)
            ? $unratedThings[array_rand($unratedThings)]->getThing()
            : null;
    }

    /**
     * @return array<string, RatedThing>
     */
    public function getUnratedThings(): array
    {
        $unratedThings = [];

        foreach ($this->ratedThings as $thingHash => $ratedThing) {
            if (!$ratedThing->isRated()) {
                $unratedThings[$thingHash] = $ratedThing;
            }
        }

        return $unratedThings;
    }

    public function getCurrentRatedThing(): ?RatedThing
    {
        return $this->currentRatedThing;
    }

    public function setCurrentRatedThing(Thing $currentRatedThing): void
    {
        $ratedThing = $this->ratedThings[$currentRatedThing->getHash()];

        $this->currentRatedThing = $ratedThing;
    }

    public function isThingFullyRated(Thing $ratedThing): bool
    {
        return $this->ratedThings[$ratedThing->getHash()]->countRatedPlayers() === count($this->players);
    }

    public function generateResult(): array
    {
        $thingsRatings = [];

        foreach ($this->ratedThings as $ratedThing) {
            $thingsRatings[$ratedThing->getThing()->getValue()] = $ratedThing->averageRating();
        }

        arsort($thingsRatings);

        return $thingsRatings;
    }

    public function isPlayerMaster(?Player $player): bool
    {
        return $this->getMaster()->getId() === $player->getId();
    }
}
