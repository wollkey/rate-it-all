<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\LeaveGameUseCase;
use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\ForbiddenActionException;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\AsTelegramCommand;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\TelegramDto;
use Phptg\BotApi\TelegramBotApi;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramCommand(self::COMMAND_NAME, inputTypes: [InputType::Text, InputType::Callback])]
final readonly class LeaveGameCommand
{
    public const string COMMAND_NAME = '/leave_game';

    public function __construct(
        private TelegramBotApi $telegram,
        private TranslatorInterface $translator,
        private PlayerRepository $playerRepository,
        private LeaveGameUseCase $leaveGameUseCase,
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
            $this->sendGameNotFoundMessage($telegramDto, $player);

            return;
        } catch (ForbiddenActionException) {
            $this->sendForbiddenActionMessage($telegramDto, $player);

            return;
        }

        if ($telegramDto->isCallback()) {
            $this->telegram->answerCallbackQuery(
                callbackQueryId: $telegramDto->callbackQuery->id,
                showAlert: false,
            );

            $this->telegram->editMessageText(
                text: 'See you at another game ![🪇](tg://emoji?id=5368324170671202286)',
                chatId: $telegramDto->message->chat->id,
                messageId: $telegramDto->message->messageId,
                replyMarkup: new InlineKeyboardMarkup([
                    [
                        new InlineKeyboardButton(
                            text: '🎮 '.$this->translator->trans('Create new game'),
                            callbackData: CreateGameCommand::COMMAND_NAME,
                        ),
                    ],
                ]),
            );
        } else {
            $this->telegram->sendMessage(
                chatId: $telegramDto->message->chat->id,
                text: 'See you at another game ![🪇](tg://emoji?id=5368324170671202286)',
                parseMode: 'MarkdownV2',
                replyMarkup: new InlineKeyboardMarkup([
                    [
                        new InlineKeyboardButton(
                            text: '💀' . $this->translator->trans('Create'),
                            callbackData: CreateGameCommand::COMMAND_NAME,
                        ),
                    ],
                ]),
            );
        }
    }

    /**
     * @throws \Exception
     */
    private function sendGameNotFoundMessage(TelegramDto $telegramDto, Player $player): void
    {
        $message = $this->translator->trans('You are not in any game').PHP_EOL.$this->translator->trans('Create a game or join an existing one');
        $keyboard = new InlineKeyboardMarkup([
            [
                new InlineKeyboardButton(
                    text: $this->translator->trans('Create'),
                    callbackData: CreateGameCommand::COMMAND_NAME,
                ),
            ],
        ]);

        if ($telegramDto->isCallback()) {
            $this->telegram->answerCallbackQuery(
                callbackQueryId: $telegramDto->callbackQuery->id,
                showAlert: false,
            );

            $this->telegram->editMessageText(
                text: $message,
                chatId: $telegramDto->message->chat->id,
                messageId: $telegramDto->message->messageId,
                replyMarkup: $keyboard,
            );
        } else {
            $this->telegram->sendMessage(
                chatId: $telegramDto->message->chat->id,
                text: $message,
                replyMarkup: $keyboard,
            );
        }
    }

    /**
     * @throws \Exception
     */
    private function sendForbiddenActionMessage(TelegramDto $telegramDto, Player $player): void
    {
        $message = $this->translator->trans('As master you can only finish the game. Do you really want it?');
        $keyboard = new InlineKeyboardMarkup([
            [
                new InlineKeyboardButton(
                    text: '💀' . $this->translator->trans('Finish the game'),
                    callbackData: FinishGameCommand::COMMAND_NAME,
                ),
            ],
        ]);

        if ($telegramDto->isCallback()) {
            $this->telegram->answerCallbackQuery(
                callbackQueryId: $telegramDto->callbackQuery->id,
                showAlert: false,
            );

            $this->telegram->editMessageText(
                text: $message,
                chatId: $telegramDto->message->chat->id,
                messageId: $telegramDto->message->messageId,
                replyMarkup: $keyboard,
            );
        } else {
            $this->telegram->sendMessage(
                chatId: $telegramDto->message->chat->id,
                text: $message,
                replyMarkup: $keyboard,
            );
        }
    }
}
