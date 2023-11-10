<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Command;

use App\Game\Domain\Model\GameSession;
use App\Game\Domain\Repository\PlayerRepositoryInterface;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class LeaveGameCommand implements BotCommandInterface
{
    public function __construct(
        private GameSession $gameSession,
        private PlayerRepositoryInterface $playerRepository,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $from = $telegramDto->getUser();

        $player = $this->playerRepository->find($from->getId());
        $game = $this->gameSession->continueGame($player);

        if ($game === null) {
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('You are not in any game').PHP_EOL.$this->translator->trans('Create a game or join an existing one'),
                [
                    'reply_markup' => [
                        'inline_keyboard' => [[
                            [
                                'text' => $this->translator->trans('Create'),
                                'callback_data' => CreateGameCommand::COMMAND_NAME,
                            ],
                            [
                                'text' => $this->translator->trans('Join'),
                                'callback_data' => JoinCommand::COMMAND_NAME,
                            ],
                        ]],
                    ],
                ],
            );

            return;
        }

        if ($game->isPlayerMaster($player)) {
            $this->telegramApi->sendMessage(
                $player->getTelegramId(),
                $this->translator->trans('You are master, you cannot leave the game, only finish it. Do you really want it?'),
                [
                    'reply_markup' => [
                        'inline_keyboard' => [[
                            [
                                'text' => $this->translator->trans('Finish game'),
                                'callback_data' => FinishGameCommand::COMMAND_NAME,
                            ],
                        ]],
                    ],
                ],
            );

            return;
        }

        $this->gameSession->leaveGame($player, $game);
        $this->gameSession->save($game);

        $this->telegramApi->sendMessage(
            $player->getTelegramId(),
            'See you at another game ![🪇](tg://emoji?id=5368324170671202286)',
            ['parse_mode' => 'MarkdownV2'],
        );
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return $this->telegramBot->getMessageCommand($telegramDto->getMessage()) === '/leave_game';
    }
}
