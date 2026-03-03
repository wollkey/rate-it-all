<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Event\GameCompleted;
use App\Game\Domain\ValueObject\RatedThingResult;
use App\Telegram\TelegramResponder;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsEventListener]
final readonly class ShowResultToPlayers
{
    public function __construct(
        private TelegramResponder $telegramResponder,
        private TranslatorInterface $translator,
    ) {
    }

    public function __invoke(GameCompleted $event): void
    {
        $results = $this->formatResults($event->game->getResults());

        $text = $this->translator->trans('The game is over!').' 🏁'
            .PHP_EOL.$this->translator->trans('Congrats! You really rated all this nonsense!').' 🎉'
            .PHP_EOL.PHP_EOL.$results;

        foreach ($event->game->getPlayers() as $player) {
            $this->telegramResponder->send(
                chatId: $player->getTelegramId(),
                text: $text,
            );
        }
    }

    /**
     * @param non-empty-list<RatedThingResult> $results
     */
    private function formatResults(array $results): string
    {
        $lines = array_map(
            static fn (RatedThingResult $result) => round($result->averageScore, 1).' 👉 '.$result->thing,
            $results,
        );

        return implode(PHP_EOL, $lines);
    }
}
