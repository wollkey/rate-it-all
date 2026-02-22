<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Http;

use App\Telegram\Domain\Event\BeginHandleWebHook;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\Infrastructure\Handler\HandlerResolver;
use App\Telegram\TelegramInput;
use App\Telegram\TelegramResponder;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsController]
#[Route('/{_locale}/telegram/hook')]
final readonly class WebHookController
{
    public function __construct(
        private EventDispatcherInterface $eventDispatcher,
        private LoggerInterface $logger,
        private TelegramResponder $telegram,
        private HandlerResolver $handlerResolver,
        private TelegramInputFactory $inputFactory,
    ) {
    }

    public function __invoke(Request $request): Response
    {
        try {
            $this->handleWebHook($request);
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable->getMessage(), ['exception' => $throwable]);
        }

        return new Response('Ok');
    }

    /**
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    private function handleWebHook(Request $request): void
    {
        $telegramInput = $this->inputFactory->createFromRequest($request);

        $this->eventDispatcher->dispatch(new BeginHandleWebHook($telegramInput));

        $handler = $this->handlerResolver->resolve($telegramInput);

        if ($handler !== null) {
            $this->executeHandler($handler, $telegramInput);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function executeHandler(callable $handler, TelegramInput $telegramInput): void
    {
        try {
            $handler($telegramInput);
        } catch (TelegramException $exception) {
            $this->logger->error(json_encode([
                'error' => $exception->getMessage(),
                'previous' => $exception->getPrevious()?->getMessage(),
                'trace' => $exception->getTrace(),
            ]));
            $exceptionMessage = !empty($exception->getMessage()) ? $exception->getMessage() : 'Unknown error';
            $this->telegram->reply($telegramInput, $exceptionMessage);
        }
    }
}
