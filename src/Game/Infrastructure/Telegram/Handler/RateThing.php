<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\RateThingUseCase;
use App\Game\Domain\Exception\GameException;
use App\Game\Domain\Exception\PlayerNotFoundException;
use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\GameState;
use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\Repository\PlayerRepository;
use App\Game\Domain\ValueObject\Score;
use App\Game\Infrastructure\Telegram\Handler\Resolver\OnGameState;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\TelegramInput;
use App\Telegram\TelegramResponder;
use Symfony\Contracts\Translation\TranslatorInterface;

#[OnGameState(GameState::Rating)]
#[AsTelegramHandler]
final readonly class RateThing
{
    public function __construct(
        private PlayerRepository $playerRepository,
        private GameRepository $gameRepository,
        private TranslatorInterface $translator,
        private RateThingUseCase $rateThingUseCase,
        private TelegramResponder $telegramResponder,
    ) {
    }

    /**
     * @throws PlayerNotFoundException
     */
    public function __invoke(TelegramInput $telegramInput): void
    {
        $player = $this->playerRepository->get($telegramInput->user->id);

        try {
            $game = $this->gameRepository->findActiveByPlayer($player);
            $thing = $game?->getCurrentThing()?->getValue() ?? '?';

            $rating = new Score((int) $telegramInput->callbackQuery->data);
            ($this->rateThingUseCase)($player, $rating);

            $this->telegramResponder->editMessage(
                $telegramInput,
                "$thing — $rating->value ⭐",
            );
        } catch (ThingIsAlreadyRatedException) {
            $this->telegramResponder->send(
                $player->getTelegramId(),
                implode(PHP_EOL, [$this->translator->trans('You must not change your rating. Choose wisely.')])
            );
        } catch (GameException $exception) {
            throw new TelegramException($exception->getMessage());
        }
    }
}
