<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\PlayerHasJoined;
use Phptg\BotApi\TelegramBotApi;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class NotifyPlayerAboutJoiningGame
{
    public function __construct(
        private TelegramBotApi $telegramApi,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(PlayerHasJoined $event): void
    {
        $this->telegramApi->sendMessage(
            $event->getPlayer()->getTelegramId(),
            $this->translator->trans('You\'ve successfully joined the game.')
            .PHP_EOL
            .$this->translator->trans('Be ready, the adventure is about to begin...')
        );
    }
}
