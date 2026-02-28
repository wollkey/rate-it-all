<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\GameTerminated;
use App\Game\Infrastructure\Telegram\Handler\CreateGame;
use App\Telegram\TelegramResponder;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class OnGameTerminated
{
    public function __construct(
        private TranslatorInterface $translator,
        private TelegramResponder $telegramResponder,
    ) {
    }

    public function __invoke(GameTerminated $event): void
    {
        $game = $event->game;
        $masterName = $game->getMaster()->getFirstName();

        foreach ($game->getPlayers() as $player) {
            $this->telegramResponder->send(
                chatId: $player->getTelegramId(),
                text: $this->translator->trans(
                    'Game was ended by master masterName',
                    ['masterName' => $masterName],
                ),
                keyboardMarkup: new InlineKeyboardMarkup([[
                    new InlineKeyboardButton(
                        text: '🎮 '.$this->translator->trans('Create new game'),
                        callbackData: CreateGame::COMMAND_NAME,
                    ),
                ]]),
            );
        }
    }
}
