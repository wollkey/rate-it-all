<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\AddThingUseCase;
use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\ThingIsAlreadyInTheListException;
use App\Game\Domain\Exception\ThingsPlayerLimitReachedException;
use App\Game\Domain\Model\Game;
use App\Game\Domain\ValueObject\Thing;
use App\Game\Infrastructure\UserResolver\PlayerResolver;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class AddThingCommand implements BotCommandInterface
{
    public function __construct(
        private AddThingUseCase $addThingUseCase,
        private Game $game,
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
        } catch (GameNotFoundException) {
            $this->handleGameNotFound($player);

            return;
        } catch (ThingIsAlreadyInTheListException) {
            $this->telegramApi->sendMessage($player->getTelegramId(), $this->translator->trans('This thing is already exist'));

            return;
        } catch (ThingsPlayerLimitReachedException) {
            $this->telegramApi->sendMessage($player->getTelegramId(), $this->translator->trans('Wait other players...'));
            $this->telegramBot->stopProcessingCommand($player->getTelegramId());
        }

        // TODO Move to event subscriber or listener
        $gameSession = $this->game->findSessionByPlayer($player);
        if ($gameSession->playerThingLimitReached($player->getId())) {
            $this->telegramBot->stopProcessingCommand($player->getTelegramId());

            $allThingsMessage = $this->translator->trans('Great job. Wait other players...');
            $this->telegramApi->sendMessage($player->getTelegramId(), $allThingsMessage);

            if ($gameSession->totalThingLimitReached()) {
                $this->telegramApi->sendMessage(
                    $gameSession->getMaster()->getTelegramId(),
                    $this->translator->trans('Every players is ready.'),
                    [
                        'reply_markup' => [
                            'inline_keyboard' => [[
                                [
                                    'text' => $this->translator->trans("Let's have some fun!"),
                                    'callback_data' => StartRatingThingCommand::COMMAND_NAME,
                                ],
                            ]],
                        ],
                    ],
                );
            }

            return;
        }

        $this->telegramApi->sendMessage($player->getTelegramId(), $this->translator->trans('Great, enter the next thing:'));
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return false;
    }

    private function handleGameNotFound(Player $player): void
    {
        $this->telegramApi->sendMessage($player->getTelegramId(), $this->translator->trans('This thing is already exist'));
    }
}
