<?php

declare(strict_types=1);

namespace App\Game\Domain\Model;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Repository\GameSessionRepositoryInterface;
use App\Game\Domain\ValueObject\ThingsPerPlayer;
use Ramsey\Uuid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class Game
{
    public const MIN_THINGS_PER_PLAYER = 1;
    public const MAX_THINGS_PER_PLAYER = 5;

    public function __construct(
        private GameSessionRepositoryInterface $sessionRepository,
        private TranslatorInterface $translator,
    ) {
    }

    public function description(): string
    {
        return $this->translator->trans(
            'In this game you need to rate any things that come to your mind: *red color*, *hand washing*, *a small salary*, *anything*...'
        );
    }

    public function rules(): array
    {
        return array_map($this->translator->trans(...), [
            'Create a game or join an existing one',
            'Invite more friends',
            'Add any things that come to your mind - the weirder, the better',
            'Debate and rate with your fellow these madness things',
        ]);
    }

    public function prettyInfo(): string
    {
        return implode(PHP_EOL, [
            $this->description() . PHP_EOL,
            $this->translator->trans('Gameplay rules:'),
            ...array_map(static fn (string $rule) => "- $rule", $this->rules()),
            '',
            $this->translator->trans('Have fun!'),
        ]);
    }

    public function createSession(Player $master, ThingsPerPlayer $thingPerPlayer): GameSession
    {
        $newGame = (new GameSession($this->generateShortUuid(), $master, $thingPerPlayer));

        $this->sessionRepository->addPlayerToGame($master, $newGame->getId());

        return $newGame;
    }

    private function generateShortUuid(): string
    {
        return Uuid::uuid7()->getTimeHiAndVersionHex();
    }

    public function restartSession(GameSession $gameSession): GameSession
    {
        $this->sessionRepository->delete($gameSession);

        return $this->createSession($gameSession->getMaster(), $gameSession->getThingPerPlayer());
    }

    /**
     * @throws GameNotFoundException
     */
    public function continue(Player $player): GameSession
    {
        return $this->findSessionByPlayer($player) ?? throw new GameNotFoundException('The game not found');
    }

    public function saveSession(GameSession $gameSession): void
    {
        $this->sessionRepository->save($gameSession);
    }

    public function addPlayerToGameSession(Player $player, GameSession $gameSession): void
    {
        $gameSession->addPlayer($player);
        $this->sessionRepository->addPlayerToGame($player, $gameSession->getId());
    }

    public function leaveGame(Player $player, GameSession $gameSession): void
    {
        $gameSession->removePlayer($player);
        $this->sessionRepository->removePlayerFromGame($player);
    }

    public function finishGame(GameSession $gameSession): void
    {
        foreach ($gameSession->getPlayers() as $player) {
            $this->sessionRepository->removePlayerFromGame($player);
        }

        $this->sessionRepository->delete($gameSession);
    }

    /**
     * @throws GameNotFoundException
     */
    public function findSession(string $gameSessionId): GameSession
    {
        return
            $this->sessionRepository->find($gameSessionId)
            ?? throw new GameNotFoundException($this->translator->trans('The game with this ID not found'));
    }

    public function findSessionByPlayer(Player $player): ?GameSession
    {
        return $this->sessionRepository->findByPlayer($player->getId());
    }
}
