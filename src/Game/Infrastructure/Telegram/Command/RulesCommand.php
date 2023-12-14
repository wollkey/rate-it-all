<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Domain\Model\Game;
use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class RulesCommand implements BotCommandInterface
{
    public const COMMAND_NAME = '/rules';

    public function __construct(
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private TranslatorInterface $translator,
        private Game $game,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $this->telegramApi->sendMessage(
            $telegramDto->getUser()->getId(),
            $this->game->prettyInfo(),
            [
                'parse_mode' => 'markdown',
                'reply_markup' => [
                    'inline_keyboard' => [
                        [
                            [
                                'text' => $this->translator->trans('Create'),
                                'callback_data' => CreateGameCommand::COMMAND_NAME,
                            ],
                            [
                                'text' => $this->translator->trans('Join'),
                                'callback_data' => JoinCommand::COMMAND_NAME,
                            ],
                        ]
                    ],
                ],
            ],
        );
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return match (self::COMMAND_NAME) {
            $this->telegramBot->getMessageCommand($telegramDto->getMessage()), $telegramDto->getData() => true,
            default => false,
        };
    }
}
