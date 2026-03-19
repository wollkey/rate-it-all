<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\StartGameUseCase;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\NotEnoughPlayersException;
use App\Game\Domain\Exception\OnlyMasterCanStartException;
use App\Game\Domain\Exception\PlayerNotFoundException;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\OnCommand;
use App\Telegram\TelegramInput;
use App\Telegram\TelegramResponder;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Contracts\Translation\TranslatorInterface;

#[OnCommand(self::COMMAND_NAME)]
#[AsTelegramHandler(inputTypes: [InputType::Callback])]
final readonly class StartGame
{
    public const string COMMAND_NAME = '/start_game';

    public function __construct(
        private TelegramResponder $telegramResponder,
        private PlayerRepository $playerRepository,
        private TranslatorInterface $translator,
        private StartGameUseCase $startGameUseCase,
    ) {
    }

    /**
     * @throws PlayerNotFoundException
     */
    public function __invoke(TelegramInput $telegramInput): void
    {
        $player = $this->playerRepository->get($telegramInput->user->id);

        try {
            ($this->startGameUseCase)($player);
            $this->telegramResponder->deleteMessage($telegramInput);
        } catch (GameNotFoundException) {
            $this->telegramResponder->reply(
                $telegramInput,
                $this->translator->trans('Kick things off with a new game'),
                new InlineKeyboardMarkup([[
                    new InlineKeyboardButton(
                        text: '🎮 '.$this->translator->trans('Create'),
                        callbackData: CreateGame::COMMAND_NAME,
                    ),
                ]]),
            );
        } catch (OnlyMasterCanStartException) {
            $this->telegramResponder->answerCallbackQuery(
                callbackQueryId: $telegramInput->callbackQuery->id,
                text: $this->translator->trans('Only the game master can start the game'),
                showAlert: true,
            );
        } catch (NotEnoughPlayersException) {
            $this->telegramResponder->reply(
                $telegramInput,
                $this->translator->trans('Need at least 2 players to start'),
            );
        }
    }
}
