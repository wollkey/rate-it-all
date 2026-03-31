<?php

declare(strict_types=1);

namespace App\Tests\Game\UseCase;

use App\Game\Application\UseCase\RateThingUseCase;
use App\Game\Domain\Entity\Player;
use App\Game\Domain\Entity\ThingRating;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Game;
use App\Game\Domain\ValueObject\Score;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RateThingTest extends KernelTestCase
{
    private RateThingUseCase $useCase;
    private Player $master;
    private Game $game;

    protected function setUp(): void
    {
        $this->useCase = self::getContainer()->get(RateThingUseCase::class);
        $this->master = new Player(1, 'Alex', 'Tash');
        $player = new Player(2, 'Lena', 'Tash');
        $this->game = new Game($this->master, new ThingsPerPlayer(1));
        $this->game->join($player);
        $this->game->startCollecting($this->master);
        $this->game->addThing($this->master, 'Thing');
        $this->game->addThing($player, 'Another thing');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($this->master);
        $entityManager->persist($player);
        $entityManager->persist($this->game);
        $entityManager->flush();
    }

    public function testThingRated(): void
    {
        ($this->useCase)($this->master, new Score(1));

        $ratings = $this->game->getCurrentThingOrFail()->getRatings();
        self::assertCount(1, $ratings);
        $rating = $ratings->first();
        self::assertInstanceOf(ThingRating::class, $rating);
        self::assertEquals(new Score(1), $rating->getScore());
    }

    public function testGameNotFound(): void
    {
        $player = new Player(3, 'Thea', 'Tash');

        self::expectException(GameNotFoundException::class);

        ($this->useCase)($player, new Score(1));
    }
}
