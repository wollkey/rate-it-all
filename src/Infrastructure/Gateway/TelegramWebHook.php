<?php

declare(strict_types=1);

namespace App\Infrastructure\Gateway;

use App\Application\Dto\TelegramDto;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[Route('/telegram/hook')]
final readonly class TelegramWebHook
{
    public function __invoke(#[MapRequestPayload] TelegramDto $telegramDto): Response
    {
        return new Response('Ok');
    }
}
