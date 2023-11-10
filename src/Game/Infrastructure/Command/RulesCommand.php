<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Command;

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
    ) {
    }

    /**
     * @throws \Exception
     */
    public function execute(TelegramDto $telegramDto): void
    {
        $this->telegramApi->sendMessage(
            $telegramDto->getUser()->getId(),
            implode(PHP_EOL, [
                $this->translator->trans('In this game you need to rate any things that come to your mind: *red color*, *hand washing*, *a small salary*, *anything*...') . PHP_EOL,
                $this->translator->trans('Rules of the game:'),
                '- '.$this->translator->trans('Create a game or join an existing one'),
                '- '.$this->translator->trans('Invite your friends'),
                '- '.$this->translator->trans('Add any things that come to your mind'),
                '- '.$this->translator->trans('Rate these things with your friends'). PHP_EOL,
                $this->translator->trans('Have fun!'),
            ]),
            [
                'parse_mode' => 'markdown',
                'reply_markup' => [
                    'inline_keyboard' => [[
                        [
                            'text' => $this->translator->trans('Create a game'),
                            'callback_data' => CreateGameCommand::COMMAND_NAME,
                        ],
                        [
                            'text' => $this->translator->trans('Join the game'),
                            'callback_data' => JoinCommand::COMMAND_NAME,
                        ],
                    ]],
                ],
            ],
        );
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return $this->telegramBot->getMessageCommand($telegramDto->getMessage()) === self::COMMAND_NAME;
    }
}
