<?php

declare(strict_types=1);

namespace App\Game\Domain\Entity;

use App\Game\Domain\ValueObject\Score;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\UniqueConstraint(columns: ['thing_id', 'player_id'])]
final class ThingRating
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    public function __construct(
        #[ORM\ManyToOne(targetEntity: Thing::class, inversedBy: 'ratings')]
        #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
        private readonly Thing $thing,
        #[ORM\ManyToOne(targetEntity: Player::class)]
        #[ORM\JoinColumn(nullable: false)]
        private readonly Player $player,
        #[ORM\Embedded(class: Score::class, columnPrefix: false)]
        private readonly Score $score
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getThing(): Thing
    {
        return $this->thing;
    }

    public function getScore(): Score
    {
        return $this->score;
    }
}
