<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\FinishGameUseCase;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\OnlyMasterCanFinishException;
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
#[AsTelegramHandler(inputTypes: [InputType::Text, InputType::Callback])]
final readonly class FinishGame
{
    public const string COMMAND_NAME = '/finish_game';

    public function __construct(
        private PlayerRepository $playerRepository,
        private TranslatorInterface $translator,
        private FinishGameUseCase $finishGameUseCase,
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
            ($this->finishGameUseCase)($player);
        } catch (GameNotFoundException) {
            $this->telegramResponder->reply(
                $telegramInput,
                $this->translator->trans('You are not in any game').PHP_EOL.$this->translator->trans('Create a game or join an existing one'),
                new InlineKeyboardMarkup([
                    [
                        new InlineKeyboardButton(
                            text: $this->translator->trans('Create'),
                            callbackData: CreateGame::COMMAND_NAME,
                        ),
                    ],
                ]),
            );

            return;
        } catch (OnlyMasterCanFinishException) {
            $this->telegramResponder->reply(
                $telegramInput,
                $this->translator->trans('Only the game master can end this party. You can gracefully exit, though.'),
                new InlineKeyboardMarkup([
                    [
                        new InlineKeyboardButton(
                            text: '💀'.$this->translator->trans('Leave the game'),
                            callbackData: LeaveGame::COMMAND_NAME,
                        ),
                    ],
                ]),
            );

            return;
        }

        $this->telegramResponder->reply(
            $telegramInput,
            $this->translator->trans('The game is over!').' 🏁',
            new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: '🎮 '.$this->translator->trans('Create new game'),
                        callbackData: CreateGame::COMMAND_NAME,
                    ),
                ],
            ])
        );
    }
}
