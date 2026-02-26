<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\RateThingUseCase;
use App\Game\Domain\Exception\GameException;
use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\GameState;
use App\Game\Domain\Repository\PlayerRepository;
use App\Game\Domain\ValueObject\Rating;
use App\Game\Infrastructure\Telegram\Handler\Resolver\OnGameState;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\TelegramInput;
use App\Telegram\TelegramResponder;
use Symfony\Contracts\Translation\TranslatorInterface;

#[OnGameState(GameState::Rating)]
#[AsTelegramHandler]
final readonly class RateTheThing
{
    public function __construct(
        private PlayerRepository $playerRepository,
        private TranslatorInterface $translator,
        private RateThingUseCase $rateThingUseCase,
        private TelegramResponder $telegramResponder,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(TelegramInput $telegramInput): void
    {
        $player = $this->playerRepository->find($telegramInput->user->id);

        try {
            $rating = new Rating((int) $telegramInput->callbackQuery->data);
            ($this->rateThingUseCase)($player, $rating);

            $this->telegramResponder->deleteMessage($telegramInput);
        } catch (\InvalidArgumentException) {
            $this->telegramResponder->reply(
                $telegramInput,
                $this->translator->trans('Enter a number from 1 to 10'),
            );

            return;
        } catch (ThingIsAlreadyRatedException) {
            $this->telegramResponder->send(
                $player->getTelegramId(),
                implode(PHP_EOL, [$this->translator->trans('You must not change you rating. Choose wisely.')])
            );

            return;
        } catch (GameException $exception) {
            throw new TelegramException($exception->getMessage());
        }
    }
}
