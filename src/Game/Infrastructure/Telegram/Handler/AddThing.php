<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\AddThingUseCase;
use App\Game\Domain\Exception\GameException;
use App\Game\Domain\Exception\ThingIsAlreadyInTheListException;
use App\Game\Domain\Exception\ThingsPlayerLimitReachedException;
use App\Game\Domain\GameState;
use App\Game\Domain\Repository\PlayerRepository;
use App\Game\Domain\ValueObject\Thing;
use App\Game\Infrastructure\Telegram\Handler\Resolver\OnGameState;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\TelegramInput;
use App\Telegram\TelegramResponder;
use Symfony\Contracts\Translation\TranslatorInterface;

#[OnGameState(GameState::Collecting)]
#[AsTelegramHandler([InputType::Text])]
final readonly class AddThing
{
    public function __construct(
        private AddThingUseCase $addThingUseCase,
        private PlayerRepository $playerRepository,
        private TelegramResponder $telegramResponder,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(TelegramInput $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);
        $thing = $telegramDto->message->text;

        try {
            ($this->addThingUseCase)($player, new Thing($thing));
        } catch (ThingIsAlreadyInTheListException) {
            $this->telegramResponder->send(
                $player->getTelegramId(),
                $this->translator->trans('This is already on the list of madness list'),
            );

            return;
        } catch (ThingsPlayerLimitReachedException) {
            $this->telegramResponder->reply(
                $player->getTelegramId(),
                $this->translator->trans('Wait other players...'),
            );
        } catch (GameException|\InvalidArgumentException $exception) {
            throw new TelegramException($exception->getMessage(), previous: $exception);
        }
    }
}
