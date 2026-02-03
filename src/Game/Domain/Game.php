<?php

declare(strict_types=1);

namespace App\Game\Domain;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Entity\Thing;
use App\Game\Domain\Exception\ForbiddenActionException;
use App\Game\Domain\Exception\PlayerAlreadyInGameException;
use App\Game\Domain\Exception\ThingIsAlreadyInTheListException;
use App\Game\Domain\Exception\ThingListIsEmptyException;
use App\Game\Domain\Exception\ThingsPlayerLimitReachedException;
use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: GameRepository::class)]
final class Game
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'uuid', unique: true)]
    private Uuid $code;

    #[ORM\Column(length: 20, enumType: GameState::class)]
    private GameState $state;

    #[ORM\Column(type: 'smallint')]
    private int $thingsPerPlayer;

    /** @var Collection<int, Player> */
    #[ORM\ManyToMany(targetEntity: Player::class, inversedBy: 'game')]
    #[ORM\JoinTable(name: 'game_player')]
    private Collection $players;

    /** @var Collection<int, Thing> */
    #[ORM\OneToMany(targetEntity: Thing::class, mappedBy: 'game', cascade: ['persist'])]
    private Collection $things;

    #[ORM\OneToOne(targetEntity: Thing::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Thing $currentThing = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Player::class)]
        #[ORM\JoinColumn(nullable: false)]
        private readonly Player $master,
        #[ORM\Column]
        ThingsPerPlayer $thingsPerPlayer,
    ) {
        $this->code = new UuidV7();
        $this->state = GameState::Waiting;
        $this->thingsPerPlayer = $thingsPerPlayer->getValue();
        $this->createdAt = new \DateTimeImmutable();
        $this->players = new ArrayCollection();
        $this->things = new ArrayCollection();

        $this->players->add($master);
    }

    public function join(Player $player): void
    {
        if ($this->hasPlayer($player)) {
            throw new PlayerAlreadyInGameException("Player {$player->getId()} already in game");
        }

        if (!$this->state->canJoin()) {
            throw new ForbiddenActionException('Cannot join game in current state');
        }

        $this->players->add($player);
    }

    public function leave(Player $player): void
    {
        if ($this->isMaster($player)) {
            throw new ForbiddenActionException('Master cannot leave the game');
        }

        $this->players->removeElement($player);
    }

    public function hasPlayer(Player $targetPlayer): bool
    {
        return $this->players->exists(fn (int $key, Player $player): bool => $player->getId() === $targetPlayer->getId());
    }

    /**
     * @throws ForbiddenActionException
     * @throws ThingsPlayerLimitReachedException
     * @throws ThingIsAlreadyInTheListException
     */
    public function addThing(Player $author, string $value): Thing
    {
        if (!$this->hasPlayer($author)) {
            throw new ForbiddenActionException('Only players can add things');
        }

        if (!$this->state->canAddThings()) {
            throw new ForbiddenActionException('Cannot add things in current state');
        }

        if ($this->isPlayerThingLimitReached($author)) {
            throw new ThingsPlayerLimitReachedException("Player reached limit of {$this->thingsPerPlayer} things");
        }

        $normalizedValue = mb_strtolower(trim($value));

        if ($this->thingExists($normalizedValue)) {
            throw new ThingIsAlreadyInTheListException("Thing '$value' already exists in this game");
        }

        $thing = new Thing($this, $author, $normalizedValue);
        $this->things->add($thing);

        return $thing;
    }

    public function isPlayerThingLimitReached(Player $player): bool
    {
        return $this->countPlayerThings($player) >= $this->thingsPerPlayer;
    }

    public function isTotalThingLimitReached(): bool
    {
        return $this->things->count() >= $this->players->count() * $this->thingsPerPlayer;
    }

    public function startCollecting(): void
    {
        if ($this->players->count() < 2) {
            throw new ForbiddenActionException('Need at least 2 players to start'); // TODO Использовать более логичное исключение
        }

        $this->state = GameState::Collecting;
    }

    public function startRating(): void
    {
        if ($this->things->isEmpty()) {
            throw new ThingListIsEmptyException('Cannot start rating without things');
        }

        $this->state = GameState::Rating;
        $this->pickNextThing();
    }

    public function rate(Player $player, int $score): void
    {
        if (!$this->hasPlayer($player)) {
            throw new ForbiddenActionException('Only players can rate');
        }

        if (!$this->state->canRate()) {
            throw new ForbiddenActionException('Cannot rate in current state');
        }

        if ($this->currentThing === null) {
            throw new ForbiddenActionException('No thing to rate currently');
        }

        $this->currentThing->rate($player, $score);
    }

    public function nextThing(): bool
    {
        if (!$this->isCurrentThingFullyRated()) {
            throw new ForbiddenActionException('Current thing is not fully rated yet');
        }

        return $this->pickNextThing();
    }

    public function isCurrentThingFullyRated(): bool
    {
        if ($this->currentThing === null) {
            return false;
        }

        return $this->currentThing->isFullyRatedBy($this->getPlayersCount());
    }

    /** @return list<Player> */
    public function getPlayersWhoNotRated(): array
    {
        if ($this->currentThing === null) {
            return [];
        }

        return $this->currentThing->getPlayersWhoNotRated($this->players);
    }

    /**
     * @return array<string, float>
     *
     * @throws ForbiddenActionException
     */
    public function getResults(): array
    {
        if (!$this->isFinished()) {
            throw new ForbiddenActionException('Game is not finished yet');
        }

        $results = [];
        foreach ($this->things as $thing) {
            $results[$thing->getValue()] = $thing->getAverageScore();
        }

        arsort($results);

        return $results;
    }

    public function finish(): void
    {
        $this->state = GameState::Finished;
    }

    public function isFinished(): bool
    {
        return $this->state === GameState::Finished;
    }

    public function getMaster(): Player
    {
        return $this->master;
    }

    public function isMaster(Player $player): bool
    {
        return $this->getMaster()->getId() === $player->getId();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): Uuid
    {
        return $this->code;
    }

    public function getState(): GameState
    {
        return $this->state;
    }

    public function getThingsPerPlayer(): int
    {
        return $this->thingsPerPlayer;
    }

    /**
     * @return Collection<Player>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function getThings(): Collection
    {
        return $this->things;
    }

    public function getCurrentThing(): ?Thing
    {
        return $this->currentThing;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    private function pickNextThing(): bool
    {
        $unratedThings = $this->getUnratedThings();

        if (empty($unratedThings)) {
            $this->currentThing = null;
            $this->state = GameState::Finished;

            return false;
        }

        $this->currentThing = $unratedThings[array_rand($unratedThings)];

        return true;
    }

    /** @return list<Thing> */
    private function getUnratedThings(): array
    {
        $playerCount = $this->players->count();
        $unrated = [];

        foreach ($this->things as $thing) {
            if (!$thing->isFullyRated($playerCount)) {
                $unrated[] = $thing;
            }
        }

        return $unrated;
    }

    private function countPlayerThings(Player $player): int
    {
        $count = 0;
        foreach ($this->things as $thing) {
            if ($thing->getAuthor()->getId() === $player->getId()) {
                ++$count;
            }
        }

        return $count;
    }

    public function thingExists(string $value): bool
    {
        foreach ($this->things as $thing) {
            if ($thing->getValue() === $value) {
                return true;
            }
        }

        return false;
    }

    private function getPlayersCount(): int
    {
        return $this->players->count();
    }
}
