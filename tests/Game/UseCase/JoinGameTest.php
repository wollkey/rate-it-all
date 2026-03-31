<?php

declare(strict_types=1);

namespace App\Tests\Game\UseCase;

use App\Game\Application\UseCase\JoinGameUseCase;
use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\PlayerAlreadyInAnotherGameException;
use App\Game\Domain\Game;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Uid\UuidV7;

final class JoinGameTest extends KernelTestCase
{
    private JoinGameUseCase $useCase;
    private EntityManagerInterface $entityManager;
    private Player $master;
    private Game $game;

    protected function setUp(): void
    {
        $this->useCase = self::getContainer()->get(JoinGameUseCase::class);
        $this->master = new Player(1, 'Alex', 'Tash');
        $this->game = new Game($this->master, new ThingsPerPlayer(1));

        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->entityManager->persist($this->master);
        $this->entityManager->persist($this->game);
        $this->entityManager->flush();
    }

    public function testPlayerJoined(): void
    {
        $player = new Player(2, 'Lena', 'Tash');
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        ($this->useCase)($player, $this->game->getId());

        self::assertCount(2, $this->game->getPlayers());
        self::assertTrue($this->game->getPlayers()->contains($player));
    }

    public function testPlayerAlreadyInAnotherGame(): void
    {
        $player = new Player(2, 'Lena', 'Tash');
        $anotherGame = new Game($player, new ThingsPerPlayer(1));
        $this->entityManager->persist($player);
        $this->entityManager->persist($anotherGame);
        $this->entityManager->flush();

        self::expectException(PlayerAlreadyInAnotherGameException::class);

        ($this->useCase)($player, $this->game->getId());
    }

    public function testGameNotFound(): void
    {
        $player = new Player(2, 'Lena', 'Tash');
        $this->entityManager->persist($player);
        $this->entityManager->flush();

        self::expectException(GameNotFoundException::class);

        ($this->useCase)($player, new UuidV7());
    }
}
