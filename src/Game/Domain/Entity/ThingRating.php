<?php

declare(strict_types=1);

namespace App\Game\Domain\Entity;

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
        private Thing $thing,
        #[ORM\ManyToOne(targetEntity: Player::class)]
        #[ORM\JoinColumn(nullable: false)]
        private Player $player,
        #[ORM\Column(type: 'smallint')]
        private int $score,
    ) {
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getThing(): Thing
    {
        return $this->thing;
    }

    public function setThing(Thing $thing): ThingRating
    {
        $this->thing = $thing;

        return $this;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function setPlayer(Player $player): ThingRating
    {
        $this->player = $player;

        return $this;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): ThingRating
    {
        $this->score = $score;

        return $this;
    }
}
