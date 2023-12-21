<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\Command;

use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Contracts\Translation\TranslatorInterface;

final readonly class StartCommand implements BotCommandInterface
{
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
                $this->translator->trans('Hi there!'),
                $this->translator->trans('This is a game in which you have to rate everything that comes to your mind.'),
                $this->translator->trans('Behold the') . ' ' . RulesCommand::COMMAND_NAME,
            ]),
        );
    }

    public function supports(TelegramDto $telegramDto): bool
    {
        return $this->telegramBot->getMessageCommand($telegramDto->getMessage()) === '/start';
    }
}
