<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\AddThingUseCase;
use App\Game\Domain\Exception\GameException;
use App\Game\Domain\Exception\PlayerNotFoundException;
use App\Game\Domain\Exception\ThingIsAlreadyInTheListException;
use App\Game\Domain\Exception\ThingsPlayerLimitReachedException;
use App\Game\Domain\Exception\ThingValueTooShortException;
use App\Game\Domain\GameState;
use App\Game\Domain\Repository\PlayerRepository;
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

    /**
     * @throws PlayerNotFoundException
     */
    public function __invoke(TelegramInput $telegramInput): void
    {
        $player = $this->playerRepository->getById($telegramInput->user->id);

        try {
            ($this->addThingUseCase)($player, (string) $telegramInput->message->text);
        } catch (ThingIsAlreadyInTheListException) {
            $this->telegramResponder->send(
                $player->getTelegramId(),
                $this->translator->trans('This is already on the list of madness list'),
            );

            return;
        } catch (ThingsPlayerLimitReachedException) {
            $this->telegramResponder->reply(
                $telegramInput,
                $this->translator->trans('Wait other players...').'⏳',
            );
        } catch (ThingValueTooShortException) {
            $this->telegramResponder->reply(
                $telegramInput,
                $this->translator->trans('Thing value is too short'),
            );
        } catch (GameException|\InvalidArgumentException $exception) {
            throw new TelegramException($exception->getMessage(), previous: $exception);
        }
    }
}
