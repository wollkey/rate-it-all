<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\RateThingUseCase;
use App\Game\Domain\Exception\GameException;
use App\Game\Domain\Exception\ThingIsAlreadyRatedException;
use App\Game\Domain\ValueObject\Rating;
use App\Game\Infrastructure\UserResolver\PlayerResolver;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class RateTheThingCommand implements BotCommandInterface
{
    public function __construct(
        private PlayerResolver $playerResolver,
        private TelegramApi $telegramApi,
        private TranslatorInterface $translator,
        private RateThingUseCase $rateThingUseCase,
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
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return false;
    }
}
