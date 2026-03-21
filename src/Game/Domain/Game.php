<?php

declare(strict_types=1);

namespace App\Game\Domain;

use App\Game\Domain\Aggregate\AggregateRoot;
use App\Game\Domain\Entity\Player;
use App\Game\Domain\Entity\Thing;
use App\Game\Domain\Event\CollectingStarted;
use App\Game\Domain\Event\GameCompleted;
use App\Game\Domain\Event\GameTerminated;
use App\Game\Domain\Event\NextThingPicked;
use App\Game\Domain\Event\PlayerJoined;
use App\Game\Domain\Event\PlayerLeft;
use App\Game\Domain\Event\RatingStarted;
use App\Game\Domain\Event\ThingAdded;
use App\Game\Domain\Event\ThingRated;
use App\Game\Domain\Exception\GameNotFinishedException;
use App\Game\Domain\Exception\InvalidGameStateException;
use App\Game\Domain\Exception\MasterCannotLeaveException;
use App\Game\Domain\Exception\NoCurrentThingException;
use App\Game\Domain\Exception\NotEnoughPlayersException;
use App\Game\Domain\Exception\OnlyMasterCanFinishException;
use App\Game\Domain\Exception\OnlyMasterCanStartException;
use App\Game\Domain\Exception\PlayerAlreadyInCurrentGameException;
use App\Game\Domain\Exception\PlayerNotInGameException;
use App\Game\Domain\Exception\ThingIsAlreadyInTheListException;
use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\Exception\ThingListIsEmptyException;
use App\Game\Domain\Exception\ThingsPlayerLimitReachedException;
use App\Game\Domain\Exception\ThingValueTooShortException;
use App\Game\Domain\ValueObject\RatedThingResult;
use App\Game\Domain\ValueObject\Score;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Uid\UuidV7;

#[ORM\Entity]
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

    /**
     * @var Collection<int, Player>
     */
    #[ORM\ManyToMany(targetEntity: Player::class, inversedBy: 'game')]
    #[ORM\JoinTable(name: 'game_player')]
    private Collection $players;

    /**
     * @var Collection<int, Thing>
     */
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
        #[ORM\Embedded(class: ThingsPerPlayer::class, columnPrefix: false)]
        private readonly ThingsPerPlayer $thingsPerPlayer,
    ) {
        $this->code = new UuidV7();
        $this->state = GameState::Waiting;
        $this->createdAt = new \DateTimeImmutable();
        $this->players = new ArrayCollection();
        $this->things = new ArrayCollection();

        $this->players->add($master);
    }

    /**
     * @throws PlayerAlreadyInCurrentGameException
     * @throws InvalidGameStateException
     */
    public function join(Player $player): void
    {
        if ($this->hasPlayer($player)) {
            throw new PlayerAlreadyInCurrentGameException($this);
        }

        if (!$this->state->canJoin()) {
            throw new InvalidGameStateException();
        }

        $this->players->add($player);
        $this->addEvent(new PlayerJoined($player, $this));
    }

    public function leave(Player $player): void
    {
        if ($this->isMaster($player)) {
            throw new MasterCannotLeaveException();
        }

        $this->players->removeElement($player);
        $this->addEvent(new PlayerLeft($player, $this));

        if ($this->players->count() <= 1) {
            $this->state = GameState::Finished;
            $this->addEvent(new GameTerminated($this));
        }
    }

    public function hasPlayer(Player $targetPlayer): bool
    {
        return $this->players->exists(
            fn (int|string $key, Player $player): bool => $player->getId() === $targetPlayer->getId(),
        );
    }

    /**
     * @throws InvalidGameStateException
     * @throws PlayerNotInGameException
     * @throws ThingIsAlreadyInTheListException
     * @throws ThingListIsEmptyException
     * @throws ThingValueTooShortException
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

        if (mb_strlen($normalizedValue) < 2) {
            throw new ThingValueTooShortException();
        }

        if ($this->thingExists($normalizedValue)) {
            throw new ThingIsAlreadyInTheListException();
        }

        $thing = new Thing($this, $author, $normalizedValue);
        $this->things->add($thing);

        $this->addEvent(new ThingAdded($author, $this));

        if ($this->isTotalThingLimitReached()) {
            $this->startRating();
        }

        return $thing;
    }

    public function isPlayerThingLimitReached(Player $player): bool
    {
        return $this->countPlayerThings($player) >= $this->thingsPerPlayer->value;
    }

    /**
     * @throws NotEnoughPlayersException
     * @throws OnlyMasterCanStartException
     */
    public function startCollecting(Player $initiator): void
    {
        if (!$this->isMaster($initiator)) {
            throw new OnlyMasterCanStartException();
        }

        if ($this->players->count() < 2) {
            throw new NotEnoughPlayersException();
        }

        $this->state = GameState::Collecting;

        $this->addEvent(new CollectingStarted($this));
    }

    /**
     * @throws ThingIsAlreadyRatedException
     * @throws NoCurrentThingException
     * @throws PlayerNotInGameException
     * @throws InvalidGameStateException
     */
    public function rate(Player $player, Score $rating): void
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

        $this->currentThing->rate($player, $rating);
        $this->addEvent(new ThingRated($player, $this));

        if ($this->isCurrentThingFullyRated()) {
            $this->advanceToNextThing();
        }
    }

    public function isCurrentThingFullyRated(): bool
    {
        if ($this->currentThing === null) {
            return false;
        }

        return $this->currentThing->isFullyRatedBy($this->players->count());
    }

    /**
     * @return list<Player>
     */
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

        if ($results === []) {
            throw new \LogicException('Finished game must have things');
        }

        return $results;
    }

    /**
     * @throws OnlyMasterCanFinishException
     */
    public function finish(Player $initiator): void
    {
        if (!$this->isMaster($initiator)) {
            throw new OnlyMasterCanFinishException($this);
        }

        $this->state = GameState::Finished;
        $this->currentThing = null;
        $this->addEvent(new GameTerminated($this));
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

    /**
     * @return positive-int
     */
    public function getIdOrFail(): int
    {
        if ($this->id === null || $this->id < 1) {
            throw new \LogicException('Game has no ID yet');
        }

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

    public function getThingsPerPlayer(): ThingsPerPlayer
    {
        return $this->thingsPerPlayer;
    }

    /**
     * @return Collection<int, Player>
     */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    /**
     * @return Collection<int, Thing>
     */
    public function getThings(): Collection
    {
        return $this->things;
    }

    public function getCurrentThing(): ?Thing
    {
        return $this->currentThing;
    }

    public function getCurrentThingOrFail(): Thing
    {
        return $this->currentThing ?? throw new \LogicException('Game has no current thing');
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @throws ThingListIsEmptyException
     */
    private function startRating(): void
    {
        $this->state = GameState::Rating;
        $this->currentThing = $this->getRandomUnratedThing() ?? throw new ThingListIsEmptyException();
        $this->addEvent(new RatingStarted($this));
    }

    private function isTotalThingLimitReached(): bool
    {
        return $this->things->count() >= $this->players->count() * $this->thingsPerPlayer->value;
    }

    private function advanceToNextThing(): void
    {
        $unratedThing = $this->getRandomUnratedThing();

        if ($unratedThing === null) {
            $this->currentThing = null;
            $this->state = GameState::Finished;
            $this->addEvent(new GameCompleted($this));

            return;
        }

        $this->currentThing = $unratedThing;
        $this->addEvent(new NextThingPicked($this));
    }

    private function getRandomUnratedThing(): ?Thing
    {
        $unratedThings = $this->getUnratedThings();

        return $unratedThings === [] ? null : $unratedThings[array_rand($unratedThings)];
    }

    /**
     * @return list<Thing>
     */
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

    /**
     * @return non-negative-int
     */
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

    private function thingExists(string $value): bool
    {
        return array_any($this->things->toArray(), fn (Thing $thing) => $thing->getValue() === $value);
    }
}
