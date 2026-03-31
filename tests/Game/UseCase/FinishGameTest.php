<?php

declare(strict_types=1);

namespace App\Tests\Game\UseCase;

use App\Game\Application\UseCase\FinishGameUseCase;
use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Game;
use App\Game\Domain\GameState;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class FinishGameTest extends KernelTestCase
{
    private FinishGameUseCase $useCase;
    private EntityManagerInterface $entityManager;
    private Player $master;
    private Game $game;

    protected function setUp(): void
    {
        $this->useCase = self::getContainer()->get(FinishGameUseCase::class);
        $this->master = new Player(1, 'Alex', 'Tash');
        $this->game = new Game($this->master, new ThingsPerPlayer(1));

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->persist($this->master);
        $this->entityManager->persist($this->game);
        $this->entityManager->flush();
    }

    public function testGameFinished(): void
    {
        ($this->useCase)($this->master);

        self::assertSame(GameState::Finished, $this->game->getState());
    }

    public function testGameNotFound(): void
    {
        $player = new Player(2, 'Lena', 'Tash');
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        self::expectException(GameNotFoundException::class);

        ($this->useCase)($player);
    }
}
