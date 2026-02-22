<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\TheGameIsOver;
use App\Telegram\TelegramResponder;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class ShowResultToPlayers
{
    public function __construct(
        private TelegramResponder $telegramResponder,
    ) {
    }

    public function __invoke(TheGameIsOver $event): void
    {
        $text = $this->formatResult($event->game->getResults());

        foreach ($event->game->getPlayers() as $player) {
            $this->telegramResponder->send(
                chatId: $player->getTelegramId(),
                text: $text,
            );
        }
    }

    private function formatResult(array $generateResult): string
    {
        $preparedResult = '';

        foreach ($generateResult as $thing => $rating) {
            $preparedResult .= $rating.' 👉 '.$thing.PHP_EOL;
        }

        return $preparedResult;
    }
}
