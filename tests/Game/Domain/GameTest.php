<?php

declare(strict_types=1);

namespace App\Tests\Game\Domain;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Entity\Thing;
use App\Game\Domain\Event\CollectingStarted;
use App\Game\Domain\Event\GameCompleted;
use App\Game\Domain\Event\GameTerminated;
use App\Game\Domain\Event\NextThingPicked;
use App\Game\Domain\Event\PlayerJoined;
use App\Game\Domain\Event\PlayerLeft;
use App\Game\Domain\Event\RatingStarted;
use App\Game\Domain\Event\ThingAdded;
use App\Game\Domain\Event\ThingRated;
use App\Game\Domain\Exception\GameNotFinishedException;
use App\Game\Domain\Exception\InvalidGameStateException;
use App\Game\Domain\Exception\MasterCannotLeaveException;
use App\Game\Domain\Exception\NotEnoughPlayersException;
use App\Game\Domain\Exception\OnlyMasterCanFinishException;
use App\Game\Domain\Exception\OnlyMasterCanStartException;
use App\Game\Domain\Exception\PlayerAlreadyInCurrentGameException;
use App\Game\Domain\Exception\PlayerNotInGameException;
use App\Game\Domain\Exception\ThingIsAlreadyInTheListException;
use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\Exception\ThingsPlayerLimitReachedException;
use App\Game\Domain\Exception\ThingValueTooShortException;
use App\Game\Domain\Game;
use App\Game\Domain\GameState;
use App\Game\Domain\ValueObject\Score;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use App\Game\Infrastructure\Telegram\Handler\FinishGame;
use PHPUnit\Framework\TestCase;

final class GameTest extends TestCase
{
    private Game $game;
    private Player $master;
    private Player $player;

    protected function setUp(): void
    {
        $this->master = new Player(1, 'Alex', 'Tash');
        $this->player = new Player(2, 'Lena', 'Tash');
        $this->game = new Game($this->master, new ThingsPerPlayer(1));
    }

    public function testJoinPlayer(): void
    {
        $this->game->join($this->player);

        self::assertTrue($this->game->hasPlayer($this->player));
        self::assertCount(2, $this->game->getPlayers());
        $events = $this->game->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PlayerJoined::class, $events[0]);
    }

    public function testJoinPlayerWhichAlreadyInGame(): void
    {
        self::expectException(PlayerAlreadyInCurrentGameException::class);

        $this->game->join($this->master);
    }

    public function testJoinWhenGameStateWrong(): void
    {
        $this->game->finish($this->master);

        self::expectException(InvalidGameStateException::class);

        $this->game->join($this->player);
    }

    public function testNotLastPlayerLeavesGame(): void
    {
        $this->game->join($this->player);
        $this->game->join(new Player(3, 'Thea', 'Tash'));
        $this->game->pullEvents();

        $this->game->leave($this->player);

        self::assertCount(2, $this->game->getPlayers());
        self::assertFalse($this->game->isFinished());
        $events = $this->game->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(PlayerLeft::class, $events[0]);
    }

    public function testLastPlayerLeavesGame(): void
    {
        $this->game->join($this->player);
        $this->game->pullEvents();

        $this->game->leave($this->player);

        self::assertTrue($this->game->isFinished());
        $events = $this->game->pullEvents();
        self::assertCount(2, $events);
        self::assertInstanceOf(PlayerLeft::class, $events[0]);
        self::assertInstanceOf(GameTerminated::class, $events[1]);
    }

    public function testMasterLeavesGame(): void
    {
        self::expectException(MasterCannotLeaveException::class);

        $this->game->leave($this->master);
    }

    public function testThingAdded(): void
    {
        $this->game->join($this->player);
        $this->game->startCollecting($this->master);
        $this->game->pullEvents();

        $this->game->addThing($this->master, 'Thing');

        $things = $this->game->getThings();
        self::assertCount(1, $things);
        $thing = $things->first();
        self::assertInstanceOf(Thing::class, $thing);
        self::assertSame('Thing', $thing->getValue());
        $events = $this->game->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ThingAdded::class, $events[0]);
    }

    public function testAllThingsAdded(): void
    {
        $this->game->join($this->player);
        $this->game->startCollecting($this->master);
        $this->game->addThing($this->player, 'Thing');
        $this->game->pullEvents();

        $this->game->addThing($this->master, 'Another thing');

        self::assertSame(GameState::Rating, $this->game->getState());
        self::assertCount(2, $this->game->getThings());
        self::assertNotNull($this->game->getCurrentThing());
        $events = $this->game->pullEvents();
        self::assertCount(2, $events);
        self::assertInstanceOf(ThingAdded::class, $events[0]);
        self::assertInstanceOf(RatingStarted::class, $events[1]);
    }

    public function testPlayerNotInGameAddsThing(): void
    {
        $this->game->join(new Player(3, 'Thea', 'Tash'));
        $this->game->startCollecting($this->master);

        self::expectException(PlayerNotInGameException::class);

        $this->game->addThing($this->player, 'Thing');
    }

    public function testPlayerAddsThingNotInCollectingState(): void
    {
        $this->game->join($this->player);

        self::expectException(InvalidGameStateException::class);

        $this->game->addThing($this->master, 'Thing');
    }

    public function testThingsLimitReached(): void
    {
        $this->game->join($this->player);
        $this->game->startCollecting($this->master);
        $this->game->addThing($this->master, 'Thing');

        self::expectException(ThingsPlayerLimitReachedException::class);

        $this->game->addThing($this->master, 'Another Thing');
    }

    public function testAddedTooShortThingValue(): void
    {
        $this->game->join($this->player);
        $this->game->startCollecting($this->master);

        self::expectException(ThingValueTooShortException::class);

        $this->game->addThing($this->master, 'T');
    }

    public function testAddedDuplicateThing(): void
    {
        $this->game->join($this->player);
        $this->game->startCollecting($this->master);
        $this->game->addThing($this->player, 'Thing');

        self::expectException(ThingIsAlreadyInTheListException::class);

        $this->game->addThing($this->master, 'Thing');
    }

    public function testStartCollecting(): void
    {
        $this->game->join($this->player);
        $this->game->pullEvents();

        $this->game->startCollecting($this->master);

        self::assertSame(GameState::Collecting, $this->game->getState());
        $events = $this->game->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(CollectingStarted::class, $events[0]);
    }

    public function testNotMasterStartsCollecting(): void
    {
        $this->game->join($this->player);
        $this->game->pullEvents();

        self::expectException(OnlyMasterCanStartException::class);

        $this->game->startCollecting($this->player);
    }

    public function testNotEnoughPlayersInGameToStartCollecting(): void
    {
        self::expectException(NotEnoughPlayersException::class);

        $this->game->startCollecting($this->master);
    }

    public function testOnePlayerRatesThing(): void
    {
        $this->prepareGameInRatingState();

        $this->game->rate($this->master, new Score(5));

        $events = $this->game->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(ThingRated::class, $events[0]);
        self::assertSame(GameState::Rating, $this->game->getState());
        self::assertNotNull($this->game->getCurrentThing());
    }

    public function testAllPlayersRateThing(): void
    {
        $this->prepareGameInRatingState();
        $this->game->rate($this->master, new Score(5));
        $this->game->pullEvents();

        $this->game->rate($this->player, new Score(5));

        $events = $this->game->pullEvents();
        self::assertCount(2, $events);
        self::assertInstanceOf(ThingRated::class, $events[0]);
        self::assertInstanceOf(NextThingPicked::class, $events[1]);
        self::assertSame(GameState::Rating, $this->game->getState());
        self::assertNotNull($this->game->getCurrentThing());
    }

    public function testLastThingIsFullyRated(): void
    {
        $this->prepareGameInRatingState();
        $this->game->rate($this->master, new Score(5));
        $this->game->rate($this->player, new Score(5));
        $this->game->rate($this->master, new Score(5));
        $this->game->pullEvents();

        $this->game->rate($this->player, new Score(5));

        $events = $this->game->pullEvents();
        self::assertCount(2, $events);
        self::assertInstanceOf(ThingRated::class, $events[0]);
        self::assertInstanceOf(GameCompleted::class, $events[1]);
        self::assertSame(GameState::Finished, $this->game->getState());
        self::assertNull($this->game->getCurrentThing());
    }

    public function testPlayerNotInGameRatesThing(): void
    {
        $this->prepareGameInRatingState();

        self::expectException(PlayerNotInGameException::class);

        $this->game->rate(new Player(3, 'Thea', 'Tash'), new Score(5));
    }

    public function testWrongStateForRateThing(): void
    {
        $this->prepareGameInRatingState();

        $this->game->finish($this->master);

        self::expectException(InvalidGameStateException::class);

        $this->game->rate($this->master, new Score(5));
    }

    public function testPlayerRatesThingSecondTime(): void
    {
        $this->prepareGameInRatingState();
        $this->game->rate($this->master, new Score(5));
        $this->game->pullEvents();

        self::expectException(ThingIsAlreadyRatedException::class);

        $this->game->rate($this->master, new Score(7));
    }

    public function testFinishGame(): void
    {
        $this->game->finish($this->master);

        $events = $this->game->pullEvents();
        self::assertCount(1, $events);
        self::assertInstanceOf(GameTerminated::class, $events[0]);
        self::assertNull($this->game->getCurrentThing());
        self::assertSame(GameState::Finished, $this->game->getState());
    }

    public function testPlayerFinishesGame(): void
    {
        self::expectException(OnlyMasterCanFinishException::class);

        $this->game->finish($this->player);
    }

    public function testGetPlayersWhoNotRated(): void
    {
        $this->prepareGameInRatingState();
        $this->game->rate($this->master, new Score(5));
        $this->game->pullEvents();

        $players = $this->game->getPlayersWhoNotRated();

        self::assertCount(1, $players);
        self::assertTrue($players[0]->getId()->equals($this->player->getId()));
    }

    public function testGetResultsSortedByScore(): void
    {
        $this->prepareGameInRatingState();
        $this->game->rate($this->master, new Score(4));
        $this->game->rate($this->player, new Score(2));
        $this->game->rate($this->master, new Score(1));
        $this->game->rate($this->player, new Score(1));

        $results = $this->game->getResults();

        self::assertCount(2, $results);
        self::assertSame(3.0, $results[0]->averageScore);
        self::assertSame(1.0, $results[1]->averageScore);
    }

    public function testGetResultsThrowsIfGameNotFinished(): void
    {
        self::expectException(GameNotFinishedException::class);

        $this->game->getResults();
    }

    private function prepareGameInRatingState(): void
    {
        $this->game->join($this->player);
        $this->game->startCollecting($this->master);
        $this->game->addThing($this->player, 'Thing');
        $this->game->addThing($this->master, 'Another Thing');
        $this->game->pullEvents();
    }
}
