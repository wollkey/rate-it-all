<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler\Resolver;

use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\Infrastructure\Handler\HandlerResolver;
use App\Telegram\TelegramInput;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

#[AsTaggedItem(priority: -10)]
final readonly class GameStateHandlerResolver implements HandlerResolver
{
    public function __construct(
        #[AutowireLocator('app.game.state_handler', indexAttribute: 'key')]
        private ContainerInterface $handlers,
        private PlayerRepository $playerRepository,
        private GameRepository $gameRepository,
    ) {
    }

    public function resolve(TelegramInput $telegramInput): ?callable
    {
        $player = $this->playerRepository->find($telegramInput->user->id);
        if ($player === null) {
            return null;
        }

        $game = $this->gameRepository->findActiveByPlayer($player);
        if ($game === null) {
            return null;
        }

        $stateKey = $game->getState()->value;

        return $this->handlers->has($stateKey)
            ? $this->handlers->get($stateKey)
            : null;
    }
}
