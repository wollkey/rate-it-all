<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Model\Game;
use App\Game\Infrastructure\UserResolver\PlayerResolver;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class StartGameCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/start_game';

    public function __construct(
        private Game $game,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private PlayerResolver $playerResolver,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerResolver->getPlayer($telegramDto->getUser());

        try {
            $gameSession = $this->game->continue($player);
        } catch (GameNotFoundException) {
            $this->sendCreateNewGameMessage($player);

            return;
        }

        foreach ($gameSession->getPlayers() as $player) {
            $this->telegramBot->startProcessingCommand($player->getTelegramId(), AddThingCommand::class);
            $this->telegramApi->sendMessage($player->getTelegramId(), $this->translator->trans('Add any things that come to your mind') . ':');
        }
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->getMessage()), $telegramDto->getData() => true,
            default => false,
        };
    }

    /**
     * @throws \Exception
     */
    private function sendCreateNewGameMessage(Player $player): void
    {
        $this->telegramApi->sendMessage(
            $player->getTelegramId(),
            $this->translator->trans('First create a new game'),
            [
                'reply_markup' => [
                    'inline_keyboard' => [[
                        [
                            'text' => $this->translator->trans('Create'),
                            'callback_data' => CreateGameCommand::COMMAND_NAME,
                        ],
                    ]],
                ],
            ],
        );
    }
}
