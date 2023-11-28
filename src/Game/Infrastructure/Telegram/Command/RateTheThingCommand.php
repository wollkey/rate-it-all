<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\RateThingUseCase;
use App\Game\Application\UseCase\TakeNextThingUseCase;
use App\Game\Domain\Exception\GameException;
use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\Exception\ThingListIsEmptyException;
use App\Game\Domain\Model\Game;
use App\Game\Domain\Model\GameSession;
use App\Game\Domain\ValueObject\Rating;
use App\Game\Infrastructure\UserResolver\PlayerResolver;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class RateTheThingCommand implements BotCommandInterface
{
    public function __construct(
        private Game $game,
        private PlayerResolver $playerResolver,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private TranslatorInterface $translator,
        private RateThingUseCase $rateThingUseCase,
        private TakeNextThingUseCase $takeNextThingUseCase,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerResolver->getPlayer($telegramDto->getUser());
        $ratingText = $telegramDto->getMessage()->getText();

        if (!is_numeric($ratingText)) {
            throw new TelegramException($this->translator->trans('Rating must be a number'));
        }

        try {
            $rating = new Rating((int) $ratingText);

            ($this->rateThingUseCase)($player, $rating);
        } catch (ThingIsAlreadyRatedException) {
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                implode(PHP_EOL, [$this->translator->trans('You must not change you rating.'), $this->translator->trans('Wait other players...')]),
            );

            return;
        } catch (GameException $exception) {
            throw new TelegramException($exception->getMessage());
        }

        // TODO Move all below into event subscriber
        $gameSession = $this->game->findSessionByPlayer($player);
        $ratedThing = $gameSession->getCurrentRatedThing();
        if (!$gameSession->isThingFullyRated($ratedThing->getThing())) {
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('Okay.') . ' ' . $this->translator->trans('Wait other players...')
            );

            return;
        }

        try {
            ($this->takeNextThingUseCase)($player);
        } catch (ThingListIsEmptyException) {
            $this->handleEmptyListException($gameSession);

            return;
        }

        $gameSession = $this->game->findSessionByPlayer($player);
        foreach ($gameSession->getPlayers() as $player) {
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('Rate the next thing: nextWord', [
                    'nextWord' => $gameSession->getCurrentRatedThing()->getThing()->getValue(),
                ])
            );
        }
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return false;
    }

    private function handleEmptyListException(GameSession $gameSession)
    {
        foreach ($gameSession->getPlayers() as $player) {
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('Congrats! You really rated all this nonsense'),
                [
                    'reply_markup' => [
                        'inline_keyboard' => [[
                            [
                                'text' => $this->translator->trans('Show result'),
                                'callback_data' => ShowResultCommand::COMMAND_NAME,
                            ],
                        ]],
                    ],
                ],
            );

            $this->telegramBot->stopProcessingCommand($player->getTelegramId());
        }
    }
}
