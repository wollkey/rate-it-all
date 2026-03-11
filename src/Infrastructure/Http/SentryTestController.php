<?php

declare(strict_types=1);

namespace App\Infrastructure\Http;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;

#[AsController]
#[Route('/_sentry-test')]
final readonly class SentryTestController
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(): Response
    {
        // the following code will test if monolog integration logs to sentry
        $this->logger->error('My custom logged error.', ['some' => 'Context Data']);
        // the following code will test if an uncaught exception logs to sentry
        throw new \RuntimeException('Example exception.');
    }
}
