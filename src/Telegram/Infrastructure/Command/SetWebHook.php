<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Command;

use App\Telegram\Infrastructure\Gateway\TelegramApi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:telegram-set-webhook')]
class SetWebHook extends Command
{
    public function __construct(
        private readonly TelegramApi $telegram,
        private readonly string $webhookUrl,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->telegram->setWebHook($this->webhookUrl);

        $output->writeln('<info>Webhook was successfully set!</info>');

        return Command::SUCCESS;
    }
}
