<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\TelegramDto;
use Phptg\BotApi\TelegramBotApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class StartGameCommand
{
    public const string COMMAND_NAME = '/start_game';

    public function __construct(
        private TelegramBotApi $telegramApi,
        private PlayerRepository $playerRepository,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);

        try {
            $gameSession = $this->game->continue($player);

            if (!$gameSession->isPlayerMaster($player)) {
                $this->telegramApi->sendMessage(
                    $player->getTelegramId(),
                    $this->translator->trans('The action is not available'),
                );

                return;
            }

            $this->telegramBot->removeEditedMessage($player->getTelegramId());
        } catch (GameNotFoundException) {
            $this->sendCreateNewGameMessage($player);

            return;
        }

        foreach ($gameSession->getPlayers() as $player) {
            $this->telegramBot->startProcessingCommand($player->getTelegramId(), AddThingCommand::class);
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('Add any crazy thing that came into your head:')
            );
        }
    }

    /**
     * @throws \Exception
     */
    private function sendCreateNewGameMessage(Player $player): void
    {
        $this->telegramApi->sendMessage(
            $player->getTelegramId(),
            $this->translator->trans('Kick things off with a new game'),
            [
                'text' => $this->translator->trans('Create'),
                'callback_data' => CreateGameCommand::COMMAND_NAME,
            ],
        );
    }
}
