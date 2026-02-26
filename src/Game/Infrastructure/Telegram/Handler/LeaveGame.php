<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\LeaveGameUseCase;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\MasterCannotLeaveException;
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
final readonly class LeaveGame
{
    public const string COMMAND_NAME = '/leave_game';

    public function __construct(
        private TranslatorInterface $translator,
        private PlayerRepository $playerRepository,
        private LeaveGameUseCase $leaveGameUseCase,
        private TelegramResponder $telegramResponder,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(TelegramInput $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);

        try {
            ($this->leaveGameUseCase)($player);
        } catch (GameNotFoundException) {
            $this->telegramResponder->reply(
                $telegramDto,
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
        } catch (MasterCannotLeaveException) {
            $this->telegramResponder->reply(
                $telegramDto,
                $this->translator->trans('As master you can only finish the game. Do you really want it?'),
                new InlineKeyboardMarkup([
                    [
                        new InlineKeyboardButton(
                            text: '💀'.$this->translator->trans('Finish the game'),
                            callbackData: FinishGame::COMMAND_NAME,
                        ),
                    ],
                ]),
            );

            return;
        }

        $this->telegramResponder->reply(
            $telegramDto,
            $this->translator->trans('See you at another game!'),
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
