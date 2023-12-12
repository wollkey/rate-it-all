<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\AddThingUseCase;
use App\Game\Domain\Exception\GameException;
use App\Game\Domain\Exception\ThingIsAlreadyInTheListException;
use App\Game\Domain\Exception\ThingsPlayerLimitReachedException;
use App\Game\Domain\ValueObject\Thing;
use App\Game\Infrastructure\UserResolver\PlayerResolver;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AddThingCommand implements BotCommandInterface
{
    public function __construct(
        private AddThingUseCase $addThingUseCase,
        private PlayerResolver $playerResolver,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private TranslatorInterface $translator
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerResolver->getPlayer($telegramDto->getUser());
        $thing = $telegramDto->getMessage()->getText();

        try {
            ($this->addThingUseCase)($player, new Thing($thing));
        } catch (ThingIsAlreadyInTheListException) {
            $this->telegramApi->sendMessage($player->getTelegramId(), $this->translator->trans('This thing is already exist'));

            return;
        } catch (ThingsPlayerLimitReachedException) {
            $this->telegramApi->sendMessage($player->getTelegramId(), $this->translator->trans('Wait other players...'));
            $this->telegramBot->stopProcessingCommand($player->getTelegramId());
        } catch (GameException $exception) {
            throw new TelegramException($exception->getMessage(), previous: $exception);
        }
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return false;
    }
}
