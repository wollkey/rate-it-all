<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\UseCase\JoinGameUseCase;
use App\Game\Domain\Exception\PlayerAlreadyInGameException;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\TelegramResponder;
use App\Telegram\TelegramInput;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Component\Uid\Uuid;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramHandler('/start', inputTypes: [InputType::Text])]
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
     * @throws \Exception
     */
    public function __invoke(TelegramInput $telegramDto): void
    {
        $gameCode = $this->extractGameCode($telegramDto->message->text);

        $gameCode !== null
            ? $this->joinGame($telegramDto, $gameCode)
            : $this->showWelcome($telegramDto);
    }

    private function extractGameCode(?string $text): ?Uuid
    {
        if ($text === null) {
            return null;
        }

        $parts = explode(' ', $text, 2);

        return Uuid::isValid($parts[1] ?? '') ? Uuid::fromString($parts[1]) : null;
    }

    private function joinGame(TelegramInput $telegramDto, Uuid $gameCode): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);

        try {
            ($this->joinGameUseCase)($player, $gameCode);
        } catch (PlayerAlreadyInGameException) {
            $this->telegramResponder->send(
                chatId: $player->getTelegramId(),
                text: $this->translator->trans('Already playing. Would you like to finish the current one?'),
                keyboardMarkup: new InlineKeyboardMarkup([
                    [
                        new InlineKeyboardButton(
                            text: '☠️'.$this->translator->trans('Leave the game'),
                            callbackData: LeaveGame::COMMAND_NAME,
                        ),
                    ],
                ])
            );
        }
    }

    private function showWelcome(TelegramInput $telegramDto): void
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
                    text: '📋 '.$this->translator->trans('How to Play'),
                    callbackData: ShowRules::COMMAND_NAME,
                ),
            ],
        ];

        $this->telegramResponder->send(
            chatId: $telegramDto->message->chat->id,
            text: implode(PHP_EOL, [
                $this->translator->trans('Hi there!'),
                $this->translator->trans('This is a game in which you have to rate everything that comes to your mind.'),
            ]),
            keyboardMarkup: new InlineKeyboardMarkup($keyboard),
        );
    }
}
