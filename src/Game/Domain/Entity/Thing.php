<?php

declare(strict_types=1);

namespace App\Game\Domain\Entity;

use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\Game;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
final class Thing
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    /**
     * @var Collection<ThingRating>
     */
    #[ORM\OneToMany(targetEntity: ThingRating::class, mappedBy: 'thing', cascade: ['persist', 'remove'])]
    private Collection $ratings;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'things')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private Game $game,
        #[ORM\ManyToOne(targetEntity: Player::class)]
        #[ORM\JoinColumn(nullable: false)]
        private Player $author,
        #[ORM\Column(length: 255)]
        private string $value,
    ) {
        $this->ratings = new ArrayCollection();
    }

    public function rate(Player $player, int $score): void
    {
        if ($this->hasRatingFrom($player)) {
            throw new ThingIsAlreadyRatedException("Player {$player->getId()} already rated this thing");
        }

        $this->ratings->add(new ThingRating($this, $player, $score));
    }

    public function hasRatingFrom(Player $player): bool
    {
        return array_any($this->ratings->toArray(), fn (ThingRating $rating) => $rating->getPlayer()->getId() === $player->getId());
    }

    public function isFullyRatedBy(int $playerCount): bool
    {
        return $this->ratings->count() >= $playerCount;
    }

    /**
     * @return list<Player>
     */
    public function getPlayersWhoNotRated(Collection $players): array
    {
        $ratedPlayerIds = [];
        foreach ($this->ratings as $rating) {
            $ratedPlayerIds[$rating->getPlayer()->getId()] = true;
        }

        $notRated = [];
        foreach ($players as $player) {
            if (!isset($ratedPlayerIds[$player->getId()])) {
                $notRated[] = $player;
            }
        }

        return $notRated;
    }

    public function getAverageScore(): float
    {
        if ($this->ratings->isEmpty()) {
            return 0.0;
        }

        $sum = 0;
        foreach ($this->ratings as $rating) {
            $sum += $rating->getScore();
        }

        return $sum / $this->ratings->count();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getGame(): Game
    {
        return $this->game;
    }

    public function setGame(Game $game): Thing
    {
        $this->game = $game;

        return $this;
    }

    public function getAuthor(): Player
    {
        return $this->author;
    }

    public function setAuthor(Player $author): Thing
    {
        $this->author = $author;

        return $this;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function setValue(string $value): Thing
    {
        $this->value = $value;

        return $this;
    }

    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function setRatings(Collection $ratings): Thing
    {
        $this->ratings = $ratings;

        return $this;
    }
}
