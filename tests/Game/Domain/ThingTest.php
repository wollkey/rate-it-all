<?php

declare(strict_types=1);
namespace App\Tests\Game\Domain;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Entity\Thing;
use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\Game;
use App\Game\Domain\ValueObject\Score;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\TestCase;

final class ThingTest extends TestCase
{
    private Player $player;
    private Thing $thing;

    protected function setUp(): void
    {
        $this->player = new Player(1, 'Alex', 'Tash');
        $game = new Game($this->player, new ThingsPerPlayer(1));
        $this->thing = new Thing($game, $this->player, 'Some thing');
    }

    public function testThingRated(): void
    {
        $this->thing->rate($this->player, new Score(5));

        self::assertTrue($this->thing->hasRatingFrom($this->player));
        self::assertCount(1, $this->thing->getRatings());
    }

    public function testThingRatedTwiceByOnePlayer(): void
    {
        $this->thing->rate($this->player, new Score(5));

        self::expectException(ThingIsAlreadyRatedException::class);

        $this->thing->rate($this->player, new Score(3));
    }

    public function testIsFullyRatedBy(): void
    {
        $this->thing->rate($this->player, new Score(5));

        self::assertTrue($this->thing->isFullyRatedBy(1));
        self::assertFalse($this->thing->isFullyRatedBy(2));
    }

    public function testGetAverageScore(): void
    {
        $player2 = new Player(2, 'Lena', 'Tash');
        $this->thing->rate($this->player, new Score(4));
        $this->thing->rate($player2, new Score(2));

        self::assertSame(3.0, $this->thing->getAverageScore());
    }

    public function testGetAverageScoreWithNoRatings(): void
    {
        self::assertSame(0.0, $this->thing->getAverageScore());
    }

    public function testGetPlayersWhoNotRated(): void
    {
        $player2 = new Player(2, 'Lena', 'Tash');
        $players = new ArrayCollection([$this->player, $player2]);
        $this->thing->rate($this->player, new Score(5));

        $notRated = $this->thing->getPlayersWhoNotRated($players);

        self::assertCount(1, $notRated);
        self::assertTrue($notRated[0]->getId()->equals($player2->getId()));
    }
}
