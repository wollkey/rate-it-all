<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Event\PlayerJoined;
use App\Game\Infrastructure\Telegram\Handler\StartGame;
use App\Game\Infrastructure\Telegram\Storage\GameTelegramContext;
use Phptg\BotApi\TelegramBotApi;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class NotifyMasterAboutJoinedPlayer
{
    public function __construct(
        private TranslatorInterface $translator,
        private TelegramBotApi $telegram,
        private GameTelegramContext $gameTelegramContext,
        private string $telegramBotName,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(PlayerJoined $event): void
    {
        $game = $event->game;
        $master = $game->getMaster();
        $locale = $master->getLocale();

        $chatId = $master->getTelegramId();
        $editedMessageId = $this->gameTelegramContext->getEditedMessage($chatId);

        $joinLink = "https://t.me/{$this->telegramBotName}?start={$game->getId()->toRfc4122()}";

        $this->telegram->editMessageText(
            implode(PHP_EOL, [
                $this->translator->trans('Players joined the game:', locale: $locale),
                ...$game->getPlayers()->map(static fn (Player $player) => $player->getFirstName()),
                '',
                $this->translator->trans('As soon as everyone is ready, start the game', locale: $locale).' 🚀',
            ]),
            chatId: $chatId,
            messageId: $editedMessageId,
            parseMode: 'markdown',
            replyMarkup: new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: $this->translator->trans('Start the game', locale: $locale),
                        callbackData: StartGame::COMMAND_NAME,
                    ),
                ],
                [
                    new InlineKeyboardButton(
                        text: '📤 '.$this->translator->trans('Invite friends', locale: $locale),
                        url: 'https://t.me/share/url?url='.urlencode($joinLink).'&text='.$this->translator->trans('Join the game!', locale: $locale),
                    ),
                ],
            ]),
        );
    }
}
