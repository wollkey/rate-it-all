<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\RateThingUseCase;
use App\Game\Domain\Exception\GameException;
use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\GameState;
use App\Game\Domain\Repository\PlayerRepository;
use App\Game\Domain\ValueObject\Rating;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\TelegramInput;
use App\Telegram\TelegramResponder;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramHandler(gameState: GameState::Rating)]
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
    public function __invoke(TelegramInput $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);
        $ratingText = $telegramDto->callbackQuery->data;

        if (!is_numeric($ratingText)) {
            throw new TelegramException($this->translator->trans('Rating must be a number'));
        }

        try {
            $this->telegramResponder->answerCallbackQuery($telegramDto->callbackQuery->id);

            $rating = new Rating((int) $ratingText);

            ($this->rateThingUseCase)($player, $rating);
        } catch (ThingIsAlreadyRatedException) {
            $this->telegramResponder->send(
                $player->getTelegramId(),
                implode(PHP_EOL, [$this->translator->trans('You must not change you rating. Choose wisely.')])
            );

            return;
        } catch (\InvalidArgumentException) {
            throw new TelegramException($this->translator->trans('Enter a number from 1 to 10'));
        } catch (GameException $exception) {
            throw new TelegramException($exception->getMessage());
        }
    }
}
