<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\FinishGameUseCase;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\TelegramInput;
use App\Telegram\TelegramResponder;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramHandler(self::COMMAND_NAME, inputTypes: [InputType::Text, InputType::Callback])]
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
    public function __invoke(TelegramInput $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);

        try {
            ($this->finishGameUseCase)($player);
        } catch (GameNotFoundException) {
            $this->sendGameNotFoundMessage($telegramDto);

            return;
        }

        $this->telegramResponder->reply(
            $telegramDto,
            $this->translator->trans('The game is over!'),
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

    private function sendGameNotFoundMessage(TelegramInput $telegramDto): void
    {
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
    }

    private function sendForbiddenActionMessage(TelegramInput $telegramDto): void
    {
        $this->telegramResponder->reply(
            $telegramDto,
            $this->translator->trans('As player you can only leave the game. Do you really want it?'),
            new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: '💀'.$this->translator->trans('Leave the game'),
                        callbackData: LeaveGame::COMMAND_NAME,
                    ),
                ],
            ]),
        );
    }
}
