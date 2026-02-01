<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\NextRatedThingTaken;
use App\Game\Infrastructure\Telegram\Command\RateTheThing;
use Phptg\BotApi\TelegramBotApi;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class SendNextRatedThingToPlayers
{
    public function __construct(
        private TranslatorInterface $translator,
        private TelegramBotApi $telegramApi,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(NextRatedThingTaken $event): void
    {
        $gameSession = $event->getGameSession();

        foreach ($gameSession->getPlayers() as $player) {
            $this->telegramBot->startProcessingCommand($player->getTelegramId(), RateTheThing::class);
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans(
                    'Rate the next thing: anyThing',
                    ['anyThing' => $gameSession->getCurrentRatedThing()->getThing()->getValue()]
                )
            );
        }
    }
}
