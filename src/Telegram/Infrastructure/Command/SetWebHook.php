<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Command;

use Phptg\BotApi\TelegramBotApi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:telegram:set-webhook')]
final readonly class SetWebHook
{
    public function __construct(
        private TelegramBotApi $telegram,
        private string $webhookUrl,
    ) {
    }

    public function __invoke(OutputInterface $output): int
    {
        $this->telegram->setWebHook($this->webhookUrl);

        $output->writeln('<info>Webhook was successfully set!</info>');

        return Command::SUCCESS;
    }
}
