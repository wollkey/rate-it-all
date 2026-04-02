<?php

declare(strict_types=1);

namespace App\Tests\Game\Infrastructure\Telegram\Handler;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Game;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use App\Game\Infrastructure\Telegram\Handler\Resolver\GameStateHandlerResolver;
use App\Tests\Common\CreateTelegramInput;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class GameStateHandlerResolverTest extends KernelTestCase
{
    use CreateTelegramInput;

    private GameStateHandlerResolver $resolver;
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->resolver = self::getContainer()->get(GameStateHandlerResolver::class);
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testReturnsHandlerForActiveGameState(): void
    {
        $player = new Player(1, 'Alex', 'Tash');
        $player2 = new Player(2, 'Lena', 'Tash');
        $game = new Game($player, new ThingsPerPlayer(1));
        $game->join($player2);
        $game->startCollecting($player);
        $this->em->persist($player);
        $this->em->persist($player2);
        $this->em->persist($game);
        $this->em->flush();

        $result = $this->resolver->resolve($this->createTextInput('Hello'));

        self::assertNotNull($result);
    }

    public function testReturnsNullWhenPlayerNotFound(): void
    {
        $result = $this->resolver->resolve($this->createTextInput('Hello'));

        self::assertNull($result);
    }

    public function testReturnsNullWhenPlayerHasNoActiveGame(): void
    {
        $player = new Player(1, 'Alex', 'Tash');
        $this->em->persist($player);
        $this->em->flush();

        $result = $this->resolver->resolve($this->createTextInput('Hello'));

        self::assertNull($result);
    }

    public function testReturnsNullWhenNoHandlerForState(): void
    {
        $player = new Player(1, 'Alex', 'Tash');
        $game = new Game($player, new ThingsPerPlayer(1));
        $this->em->persist($player);
        $this->em->persist($game);
        $this->em->flush();

        $result = $this->resolver->resolve($this->createTextInput('Hello'));

        self::assertNull($result);
    }
}
