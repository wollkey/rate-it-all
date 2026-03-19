<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\JoinGameUseCase;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Exception\InvalidGameStateException;
use App\Game\Domain\Exception\PlayerAlreadyInAnotherGameException;
use App\Game\Domain\Exception\PlayerAlreadyInCurrentGameException;
use App\Game\Domain\Exception\PlayerNotFoundException;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\OnCommand;
use App\Telegram\TelegramInput;
use App\Telegram\TelegramResponder;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

#[OnCommand('/start')]
#[AsTelegramHandler(inputTypes: [InputType::Text])]
final readonly class StartBot
{
    public function __construct(
        private TranslatorInterface $translator,
        private JoinGameUseCase $joinGameUseCase,
        private PlayerRepository $playerRepository,
        private TelegramResponder $telegramResponder,
    ) {
    }

    /**
     * @throws PlayerNotFoundException
     */
    public function __invoke(TelegramInput $telegramInput): void
    {
        $gameCode = $this->extractGameCode($telegramInput->message->text);

        $gameCode !== null
            ? $this->joinGame($telegramInput, $gameCode)
            : $this->showWelcome($telegramInput);
    }

    private function extractGameCode(?string $text): ?Uuid
    {
        if ($text === null) {
            return null;
        }

        $parts = explode(' ', $text, 2);

        return Uuid::isValid($parts[1] ?? '') ? Uuid::fromString($parts[1]) : null;
    }

    /**
     * @throws PlayerNotFoundException
     */
    private function joinGame(TelegramInput $telegramInput, Uuid $gameCode): void
    {
        $player = $this->playerRepository->get($telegramInput->user->id);

        try {
            ($this->joinGameUseCase)($player, $gameCode);
        } catch (GameNotFoundException) {
            $this->telegramResponder->reply(
                $telegramInput,
                $this->translator->trans('The game with this ID not found'),
            );
        } catch (PlayerAlreadyInAnotherGameException $exception) {
            if ($exception->game->isMaster($player)) {
                $this->telegramResponder->reply(
                    $telegramInput,
                    $this->translator->trans('You are the master of another game. Finish it first to join a new one'),
                    new InlineKeyboardMarkup([[
                        new InlineKeyboardButton(
                            text: '💀 '.$this->translator->trans('Finish the game'),
                            callbackData: FinishGame::COMMAND_NAME,
                        ),
                    ]]),
                );
            } else {
                $this->telegramResponder->reply(
                    $telegramInput,
                    $this->translator->trans('You are in another game. Leave it to join a new one'),
                    new InlineKeyboardMarkup([[
                        new InlineKeyboardButton(
                            text: '💀 '.$this->translator->trans('Leave the game'),
                            callbackData: LeaveGame::COMMAND_NAME,
                        ),
                    ]]),
                );
            }
        } catch (PlayerAlreadyInCurrentGameException $exception) {
            if ($exception->game->isMaster($player)) {
                $this->telegramResponder->reply(
                    $telegramInput,
                    $this->translator->trans('You are the master of this game. Would you like to finish it?'),
                    new InlineKeyboardMarkup([[
                        new InlineKeyboardButton(
                            text: '💀 '.$this->translator->trans('Finish the game'),
                            callbackData: FinishGame::COMMAND_NAME,
                        ),
                    ]]),
                );
            } else {
                $this->telegramResponder->reply(
                    $telegramInput,
                    $this->translator->trans('You are already in this game'),
                );
            }
        } catch (InvalidGameStateException) {
            $this->telegramResponder->reply(
                $telegramInput,
                $this->translator->trans('This game has already started'),
            );
        }
    }

    private function showWelcome(TelegramInput $telegramInput): void
    {
        $keyboard = [
            [
                new InlineKeyboardButton(
                    text: '🎮 '.$this->translator->trans('Create'),
                    callbackData: CreateGame::COMMAND_NAME,
                ),
            ],
            [
                new InlineKeyboardButton(
                    text: '📖 '.$this->translator->trans('How to Play'),
                    callbackData: ShowRules::COMMAND_NAME,
                ),
            ],
        ];

        $this->telegramResponder->send(
            chatId: $telegramInput->message->chat->id,
            text: implode(PHP_EOL, [
                $this->translator->trans('Hi there!').'👋',
                $this->translator->trans('This is a game in which you have to rate everything that comes to your mind.'),
            ]),
            keyboardMarkup: new InlineKeyboardMarkup($keyboard),
        );
    }
}
