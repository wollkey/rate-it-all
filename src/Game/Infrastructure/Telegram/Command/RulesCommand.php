<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Game\Domain\GameInfo;
use App\Telegram\AsTelegramCommand;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\Infrastructure\Http\TelegramResponder;
use App\Telegram\TelegramDto;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramCommand('/rules', inputTypes: [InputType::Text, InputType::Callback])]
final readonly class RulesCommand
{
    public const string COMMAND_NAME = '/rules';

    public function __construct(
        private TelegramResponder $telegram,
        private TranslatorInterface $translator,
        private GameInfo $gameInfo,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(TelegramDto $telegramDto): void
    {
        $this->telegram->reply(
            $telegramDto,
            $this->gameInfo->prettyInfo(),
            new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: '🎮 '.$this->translator->trans('Create'),
                        callbackData: CreateGameCommand::COMMAND_NAME,
                    ),
                ],
            ]),
        );
    }
}
