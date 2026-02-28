<?php

declare(strict_types=1);

namespace App\Game\Domain;

use App\Game\Domain\Aggregate\AggregateRoot;
use App\Game\Domain\Entity\Player;
use App\Game\Domain\Entity\Thing;
use App\Game\Domain\Event\GameCollectingStarted;
use App\Game\Domain\Event\NextRatedThingTaken;
use App\Game\Domain\Event\PlayerHasJoined;
use App\Game\Domain\Event\RatingStarted;
use App\Game\Domain\Event\TheGameIsOver;
use App\Game\Domain\Event\ThingHasBeenAdded;
use App\Game\Domain\Event\ThingHasBeenRated;
use App\Game\Domain\Exception\GameNotFinishedException;
use App\Game\Domain\Exception\InvalidGameStateException;
use App\Game\Domain\Exception\MasterCannotLeaveException;
use App\Game\Domain\Exception\NoCurrentThingException;
use App\Game\Domain\Exception\NotEnoughPlayersException;
use App\Game\Domain\Exception\OnlyMasterCanStartException;
use App\Game\Domain\Exception\PlayerAlreadyInCurrentGameException;
use App\Game\Domain\Exception\PlayerNotInGameException;
use App\Game\Domain\Exception\ThingIsAlreadyInTheListException;
use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\Exception\ThingsPlayerLimitReachedException;
use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\ValueObject\RatedThingResult;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity(repositoryClass: GameRepository::class)]
final class Game extends AggregateRoot
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
            throw new PlayerAlreadyInCurrentGameException($this);
        }

        if (!$this->state->canJoin()) {
            throw new InvalidGameStateException();
        }

        $this->players->add($player);
        $this->addEvent(new PlayerHasJoined($player, $this));
    }

    public function leave(Player $player): void
    {
        if ($this->isMaster($player)) {
            throw new MasterCannotLeaveException();
        }

        $this->players->removeElement($player);

        if ($this->players->count() <= 1) {
            $this->finish();
        }
    }

    public function hasPlayer(Player $targetPlayer): bool
    {
        return $this->players->exists(fn (int $key, Player $player): bool => $player->getId() === $targetPlayer->getId());
    }

    /**
     * @throws InvalidGameStateException
     * @throws PlayerNotInGameException
     * @throws ThingIsAlreadyInTheListException
     * @throws ThingsPlayerLimitReachedException
     */
    public function addThing(Player $author, string $value): Thing
    {
        if (!$this->hasPlayer($author)) {
            throw new PlayerNotInGameException();
        }

        if (!$this->state->canAddThings()) {
            throw new InvalidGameStateException();
        }

        if ($this->isPlayerThingLimitReached($author)) {
            throw new ThingsPlayerLimitReachedException();
        }

        $normalizedValue = mb_strtolower(trim($value));

        if ($this->thingExists($normalizedValue)) {
            throw new ThingIsAlreadyInTheListException();
        }

        $thing = new Thing($this, $author, $normalizedValue);
        $this->things->add($thing);

        $this->addEvent(new ThingHasBeenAdded($author, $this));

        if ($this->isTotalThingLimitReached()) {
            $this->startRating();
        }

        return $thing;
    }

    public function isPlayerThingLimitReached(Player $player): bool
    {
        return $this->countPlayerThings($player) >= $this->thingsPerPlayer;
    }

    public function startCollecting(Player $initiator): void
    {
        if (!$this->isMaster($initiator)) {
            throw new OnlyMasterCanStartException();
        }

        if ($this->players->count() < 2) {
            throw new NotEnoughPlayersException();
        }

        $this->state = GameState::Collecting;

        $this->addEvent(new GameCollectingStarted($this));
    }

    /**
     * @throws ThingIsAlreadyRatedException
     * @throws NoCurrentThingException
     * @throws PlayerNotInGameException
     * @throws InvalidGameStateException
     */
    public function rate(Player $player, int $score): void
    {
        if (!$this->hasPlayer($player)) {
            throw new PlayerNotInGameException();
        }

        if (!$this->state->canRate()) {
            throw new InvalidGameStateException();
        }

        if ($this->currentThing === null) {
            throw new NoCurrentThingException();
        }

        $this->currentThing->rate($player, $score);
        $this->addEvent(new ThingHasBeenRated($player, $this));

        if ($this->isCurrentThingFullyRated()) {
            $this->advanceToNextThing();
        }
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
     * @return non-empty-list<RatedThingResult>
     *
     * @throws GameNotFinishedException
     */
    public function getResults(): array
    {
        if (!$this->isFinished()) {
            throw new GameNotFinishedException();
        }

        $results = [];
        foreach ($this->things as $thing) {
            $results[] = new RatedThingResult(
                thing: $thing->getValue(),
                averageScore: $thing->getAverageScore(),
            );
        }

        usort(
            $results,
            static fn (RatedThingResult $a, RatedThingResult $b): int => $b->averageScore <=> $a->averageScore,
        );

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

    private function startRating(): void
    {
        $this->state = GameState::Rating;
        $this->pickNextThing();
        $this->addEvent(new RatingStarted($this));
    }

    private function isTotalThingLimitReached(): bool
    {
        return $this->things->count() >= $this->players->count() * $this->thingsPerPlayer;
    }

    private function advanceToNextThing(): void
    {
        $unratedThings = $this->getUnratedThings();

        if ($unratedThings === []) {
            $this->currentThing = null;
            $this->state = GameState::Finished;
            $this->addEvent(new TheGameIsOver($this));

            return;
        }

        $this->currentThing = $unratedThings[array_rand($unratedThings)];
        $this->addEvent(new NextRatedThingTaken($this));
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
            if (!$thing->isFullyRatedBy($playerCount)) {
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
