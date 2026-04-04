<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Handler;

use App\Game\Application\GameInfo;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\OnCommand;
use App\Telegram\TelegramInput;
use App\Telegram\TelegramResponder;
use Phptg\BotApi\Type\InlineKeyboardButton;
use Phptg\BotApi\Type\InlineKeyboardMarkup;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

#[OnCommand(self::COMMAND_NAME)]
#[AsTelegramHandler(inputTypes: [InputType::Text, InputType::Callback])]
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
    public function __invoke(TelegramInput $telegramInput): void
    {
        $this->telegram->reply(
            $telegramInput,
            $this->buildPrettyInfo(),
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

    public function buildPrettyInfo(): string
    {
        $rules = array_map(
            fn (TranslatableMessage $rule) => '- '.$rule->trans($this->translator),
            $this->gameInfo->rules(),
        );

        return implode(PHP_EOL, [
            $this->gameInfo->description()->trans($this->translator).PHP_EOL,
            $this->translator->trans('Gameplay rules:'),
            ...$rules,
            '',
            $this->translator->trans('Have fun!').' 🎊',
        ]);
    }
}
