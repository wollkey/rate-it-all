<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Telegram\AsTelegramCommand;
use App\Telegram\Domain\Enum\ChatType;
use App\Telegram\TelegramDto;
use Phptg\BotApi\TelegramBotApi;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramCommand('/start', chatType: ChatType::Private)]
final readonly class StartCommand
{
    public function __construct(
        private TelegramBotApi $telegram,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(TelegramDto $telegramDto): void
    {
        $keyboard = [
            [
                new InlineKeyboardButton(
                    text: '🎮 '.$this->translator->trans('Create'),
                    callbackData: CreateGameCommand::COMMAND_NAME,
                ),
                new InlineKeyboardButton(
                    text: '🔗 '.$this->translator->trans('Join'),
                    callbackData: JoinCommand::COMMAND_NAME,
                ),
            ],
            [
                new InlineKeyboardButton(
                    text: '📋 '.$this->translator->trans('How to Play'),
                    callbackData: RulesCommand::COMMAND_NAME,
                ),
            ],
        ];

        $this->telegram->sendMessage(
            chatId: $telegramDto->message->chat->id,
            text: implode(PHP_EOL, [
                $this->translator->trans('Hi there!'),
                $this->translator->trans('This is a game in which you have to rate everything that comes to your mind.'),
            ]),
            parseMode: 'markdown',
            replyMarkup: new InlineKeyboardMarkup($keyboard),
        );
    }
}
