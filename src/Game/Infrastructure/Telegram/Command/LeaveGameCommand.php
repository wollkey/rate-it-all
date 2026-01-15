<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\LeaveGameUseCase;
use App\Game\Domain\Exception\ForbiddenActionException;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\AsTelegramCommand;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\Infrastructure\Http\TelegramResponder;
use App\Telegram\TelegramDto;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramCommand(self::COMMAND_NAME, inputTypes: [InputType::Text, InputType::Callback])]
final readonly class LeaveGameCommand
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
    public function __invoke(TelegramDto $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);

        try {
            ($this->leaveGameUseCase)($player);
        } catch (GameNotFoundException) {
            $this->sendGameNotFoundMessage($telegramDto);

            return;
        } catch (ForbiddenActionException) {
            $this->sendForbiddenActionMessage($telegramDto);

            return;
        }

        $this->telegramResponder->reply(
            $telegramDto,
            'See you at another game ![🪇](tg://emoji?id=5368324170671202286)',
            new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: '🎮 '.$this->translator->trans('Create new game'),
                        callbackData: CreateGameCommand::COMMAND_NAME,
                    ),
                ],
            ])
        );
    }

    /**
     * @throws \Exception
     */
    private function sendGameNotFoundMessage(TelegramDto $telegramDto): void
    {
        $this->telegramResponder->reply(
            $telegramDto,
            $this->translator->trans('You are not in any game').PHP_EOL.$this->translator->trans('Create a game or join an existing one'),
            new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: $this->translator->trans('Create'),
                        callbackData: CreateGameCommand::COMMAND_NAME,
                    ),
                ],
            ]),
        );
    }

    /**
     * @throws \Exception
     */
    private function sendForbiddenActionMessage(TelegramDto $telegramDto): void
    {
        $this->telegramResponder->reply(
            $telegramDto,
            $this->translator->trans('As master you can only finish the game. Do you really want it?'),
            new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: '💀' . $this->translator->trans('Finish the game'),
                        callbackData: FinishGameCommand::COMMAND_NAME,
                    ),
                ],
            ]),
        );
    }
}
