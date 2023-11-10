<?php

declare(strict_types=1);

namespace App\Game\Application\UseCase;

use App\Game\Application\Dto\PlayerDto;
use App\Game\Domain\Model\Game;
use App\Game\Domain\Model\GameSession;
use App\Game\Domain\Model\Thing;
use App\Game\Domain\Repository\PlayerRepositoryInterface;
use App\Telegram\Domain\Exception\TelegramException;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AddThingUseCase
{
    public function __construct(
        private GameSession $gameSession,
        private PlayerRepositoryInterface $playerRepository,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(string $thing, PlayerDto $playerDto): Game
    {
        $player = $this->playerRepository->find($playerDto->getId());
        $game = $this->gameSession->continueGame($player);

        $thing = new Thing($thing);

        if ($game->thingExists($thing)) {
            throw new TelegramException($this->translator->trans('This thing is already exist'));
        }

        $game->addThing($player, $thing);
        $this->gameSession->save($game);

        return $game;
    }
}
