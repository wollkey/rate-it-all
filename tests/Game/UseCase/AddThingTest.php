<?php

declare(strict_types=1);

namespace App\Tests\Game\UseCase;

use App\Game\Application\UseCase\AddThingUseCase;
use App\Game\Domain\Entity\Player;
use App\Game\Domain\Entity\Thing;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Game;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class AddThingTest extends KernelTestCase
{
    private AddThingUseCase $useCase;
    private Player $master;
    private Game $game;

    protected function setUp(): void
    {
        $this->useCase = self::getContainer()->get(AddThingUseCase::class);
        $this->master = new Player(1, 'Alex', 'Tash');
        $player = new Player(2, 'Lena', 'Tash');
        $this->game = new Game($this->master, new ThingsPerPlayer(1));
        $this->game->join($player);
        $this->game->startCollecting($this->master);

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($this->master);
        $entityManager->persist($player);
        $entityManager->persist($this->game);
        $entityManager->flush();
    }

    public function testThingAdded(): void
    {
        ($this->useCase)($this->master, 'Thing');

        $thing = $this->game->getThings()->first();
        self::assertInstanceOf(Thing::class, $thing);
        self::assertSame('Thing', $thing->getValue());
    }

    public function testGameNotFound(): void
    {
        $player = new Player(3, 'Thea', 'Tash');

        self::expectException(GameNotFoundException::class);

        ($this->useCase)($player, 'Thing');
    }
}
