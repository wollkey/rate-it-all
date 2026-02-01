<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Domain\GameInfo;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\TelegramResponder;
use App\Telegram\TelegramInput;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Contracts\Translation\TranslatorInterface;

#[AsTelegramHandler('/rules', inputTypes: [InputType::Text, InputType::Callback])]
final readonly class ShowRules
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
    public function __invoke(TelegramInput $telegramDto): void
    {
        $this->telegram->reply(
            $telegramDto,
            $this->gameInfo->prettyInfo(),
            new InlineKeyboardMarkup([
                [
                    new InlineKeyboardButton(
                        text: '🎮 '.$this->translator->trans('Create'),
                        callbackData: CreateGame::COMMAND_NAME,
                    ),
                ],
            ]),
        );
    }
}
