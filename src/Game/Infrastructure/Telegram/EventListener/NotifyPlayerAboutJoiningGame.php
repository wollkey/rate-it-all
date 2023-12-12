<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\PlayerHasJoined;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class NotifyPlayerAboutJoiningGame
{
    public function __construct(
        private TelegramApi $telegramApi,
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
            $this->translator->trans('You have successfully joined') . PHP_EOL . $this->translator->trans(
                'Wait until the game starts...'
            )
        );
    }
}
