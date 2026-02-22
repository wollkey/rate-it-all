<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Command;

use Phptg\BotApi\FailResult;
use Phptg\BotApi\TelegramBotApi;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

#[AsCommand(name: 'app:telegram:set-webhook')]
final readonly class SetWebHook
{
    public function __construct(
        private TelegramBotApi $telegram,
        #[Autowire(env: 'TELEGRAM_WEBHOOK_URL')]
        private string $webhookUrl,
    ) {
    }

    public function __invoke(OutputInterface $output): int
    {
        $result = $this->telegram->setWebHook($this->webhookUrl);

        if ($result instanceof FailResult) {
            $output->writeln("<error>{$result->response->body}</error>");
            $output->writeln('<error>Error setting webhook!</error>');

            return Command::FAILURE;
        }

        $output->writeln('<info>Webhook was successfully set!</info>');

        return Command::SUCCESS;
    }
}
