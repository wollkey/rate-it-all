<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Application\UseCase\LeaveGameUseCase;
use App\Game\Domain\Entity\Player;
use App\Game\Domain\Exception\ForbiddenActionException;
use App\Game\Domain\Exception\GameNotFoundException;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\TelegramDto;
use Phptg\BotApi\TelegramBotApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class LeaveGameCommand
{
    public const string COMMAND_NAME = '/leave_game';

    public function __construct(
        private TelegramBotApi $telegramApi,
        private TranslatorInterface $translator,
        private PlayerRepository $playerRepository,
        private LeaveGameUseCase $leaveGameUseCase,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $player = $this->playerRepository->find($telegramDto->user->id);

        try {
            ($this->leaveGameUseCase)($player);
        } catch (GameNotFoundException) {
            $this->sendGameNotFoundMessage($player);

            return;
        } catch (ForbiddenActionException) {
            $this->sendForbiddenActionMessage($player);

            return;
        }

        $this->telegramApi->sendMessage(
            $player->getTelegramId(),
            'See you at another game ![🪇](tg://emoji?id=5368324170671202286)',
            ['parse_mode' => 'MarkdownV2'],
        );
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->message), $telegramDto->callbackQuery?->data => true,
            default => false,
        };
    }

    /**
     * @throws \Exception
     */
    private function sendGameNotFoundMessage(Player $player): void
    {
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
    }

    /**
     * @throws \Exception
     */
    private function sendForbiddenActionMessage(Player $player): void
    {
        $this->telegramApi->sendMessage(
            $player->getTelegramId(),
            $this->translator->trans('As master you can only finish the game. Do you really want it?'),
            [
                'reply_markup' => [
                    'inline_keyboard' => [[
                        [
                            'text' => $this->translator->trans('Finish the game'),
                            'callback_data' => FinishGameCommand::COMMAND_NAME,
                        ],
                    ]],
                ],
            ],
        );
    }
}
