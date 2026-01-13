<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\FinishGameUseCase;
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
final readonly class FinishGameCommand
{
    public const string COMMAND_NAME = '/finish_game';

    public function __construct(
        private PlayerRepository $playerRepository,
        private TelegramBotApi $telegram,
        private TranslatorInterface $translator,
        private FinishGameUseCase $finishGameUseCase,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(TelegramDto $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);

        try {
            ($this->finishGameUseCase)($player);
        } catch (GameNotFoundException) {
            $this->sendGameNotFoundMessage($telegramDto);

            return;
        }

        $telegramDto->isCallback()
            ? $this->handleCallback($telegramDto)
            : $this->handleText($telegramDto);
    }

    private function handleCallback(TelegramDto $telegramDto): void
    {
        $this->telegram->answerCallbackQuery(
            callbackQueryId: $telegramDto->callbackQuery->id,
            showAlert: false,
        );

        $this->telegram->editMessageText(
            text: $this->translator->trans('The game is over!'),
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
    }

    private function handleText(TelegramDto $telegramDto): void
    {
        $this->telegram->sendMessage(
            chatId: $telegramDto->message->chat->id,
            text: $this->translator->trans('The game is over!')
        );
    }

    private function sendGameNotFoundMessage(TelegramDto $telegramDto): void
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

    private function sendForbiddenActionMessage(TelegramDto $telegramDto): void
    {
        $message = $this->translator->trans('As player you can only leave the game. Do you really want it?');
        $keyboard = new InlineKeyboardMarkup([
            [
                new InlineKeyboardButton(
                    text: '💀' . $this->translator->trans('Leave the game'),
                    callbackData: LeaveGameCommand::COMMAND_NAME,
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
