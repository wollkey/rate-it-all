<?php

declare(strict_types=1);

namespace App\Game\Domain\Entity;

use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\Game;
use App\Game\Domain\ValueObject\Score;
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

    /** @var Collection<int, ThingRating> */
    #[ORM\OneToMany(targetEntity: ThingRating::class, mappedBy: 'thing', cascade: ['persist', 'remove'])]
    private Collection $ratings;

    /**
     * @param non-empty-string $value
     */
    public function __construct(
        #[ORM\ManyToOne(targetEntity: Game::class, inversedBy: 'things')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private readonly Game $game,
        #[ORM\ManyToOne(targetEntity: Player::class)]
        #[ORM\JoinColumn(nullable: false)]
        private readonly Player $author,
        #[ORM\Column(length: 255)]
        private readonly string $value,
    ) {
        $this->ratings = new ArrayCollection();
    }

    public function rate(Player $player, Score $rating): void
    {
        if ($this->hasRatingFrom($player)) {
            throw new ThingIsAlreadyRatedException();
        }

        $this->ratings->add(new ThingRating($this, $player, $rating));
    }

    public function hasRatingFrom(Player $player): bool
    {
        return array_any(
            $this->ratings->toArray(),
            fn (ThingRating $r): bool => $r->getPlayer()->getId() === $player->getId(),
        );
    }

    public function isFullyRatedBy(int $playerCount): bool
    {
        return $this->ratings->count() >= $playerCount;
    }

    /**
     * @param Collection<int, Player> $players
     *
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
            $sum += $rating->getScore()->value;
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

    public function getAuthor(): Player
    {
        return $this->author;
    }

    /**
     * @return non-empty-string
     */
    public function getValue(): string
    {
        return $this->value;
    }

    /**
     * @return Collection<int, ThingRating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }
}
