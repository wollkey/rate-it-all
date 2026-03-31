<?php

declare(strict_types=1);

namespace App\Tests\Game\UseCase;

use App\Game\Application\UseCase\CreateGameUseCase;
use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\PlayerAlreadyInAnotherGameException;
use App\Game\Domain\GameState;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CreateGameTest extends KernelTestCase
{
    private CreateGameUseCase $useCase;
    private Player $master;

    protected function setUp(): void
    {
        $this->useCase = self::getContainer()->get(CreateGameUseCase::class);
        $this->master = new Player(1, 'Alex', 'Tash');

        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $entityManager->persist($this->master);
        $entityManager->flush();
    }

    public function testGameCreated(): void
    {
        $game = ($this->useCase)($this->master, new ThingsPerPlayer(1));

        self::assertSame(GameState::Waiting, $game->getState());
        self::assertTrue($game->isMaster($this->master));
    }

    public function testPlayerAlreadyInGame(): void
    {
        ($this->useCase)($this->master, new ThingsPerPlayer(1));

        self::expectException(PlayerAlreadyInAnotherGameException::class);

        ($this->useCase)($this->master, new ThingsPerPlayer(1));
    }
}
