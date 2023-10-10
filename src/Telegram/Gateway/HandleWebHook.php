<?php

declare(strict_types=1);

namespace App\Telegram\Gateway;

use App\Telegram\Dto\TelegramResponse;
use App\Telegram\Exception\TelegramException;
use App\Telegram\TelegramBot;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[Route('/telegram/hook')]
final readonly class HandleWebHook
{
    public function __construct(
        private TelegramBot $telegramApp,
    ) {
    }

    public function __invoke(#[MapRequestPayload] TelegramResponse $telegramDto): Response
    {
        try {
            if ($this->telegramApp->isCommand($telegramDto->getMessage())) {
                $this->telegramApp->executeCommand($telegramDto->getMessage());
            }
        } catch (TelegramException $exception) {
            return new Response($exception->getMessage());
        }

        return new Response('Ok');
    }
}
