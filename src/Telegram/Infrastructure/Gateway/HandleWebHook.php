<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Gateway;

use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\Entity\TelegramRequest;
use App\Telegram\Domain\EntityExtractor\MessageExtractor;
use App\Telegram\Domain\EntityExtractor\UserExtractor;
use App\Telegram\Domain\Event\BeginHandleWebHook;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsController]
#[Route('/{_locale}/telegram/hook')]
final readonly class HandleWebHook
{
    /**
     * @param iterable<BotCommandInterface> $telegramCommands
     */
    public function __construct(
        #[TaggedIterator('app.telegram_bot.command')]
        private iterable $telegramCommands,
        private EventDispatcherInterface $eventDispatcher,
        private MessageExtractor $messageExtractor,
        private UserExtractor $userExtractor,
        private TelegramApi $telegramApi,
        private TelegramBot $telegramBot,
        private LoggerInterface $logger,
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(Request $request): Response
    {
        try {
            $this->tryHandleWebHook($request);
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable);
        }

        return new Response('Ok');
    }

    private function tryHandleWebHook(Request $request): void
    {
        $telegramRequest = $this->serializer->deserialize(
            $request->getContent(),
            TelegramRequest::class,
            JsonEncoder::FORMAT
        );

        $telegramDto = $this->prepareTelegramData($telegramRequest);

        $this->eventDispatcher->dispatch(new BeginHandleWebHook($telegramDto));

        try {
            $command = $this->resolveCommand($telegramDto);

            $command?->execute($telegramDto);
        } catch (TelegramException $exception) {
            $this->telegramApi->sendMessage($telegramDto->getUser()->getId(), $exception->getMessage());
        }
    }

    private function resolveCommand(TelegramDto $telegramDto): ?BotCommandInterface
    {
        $fromId = $telegramDto->getUser()->getId();

        foreach ($this->telegramCommands as $command) {
            if ($command->supports($telegramDto)) {
                $this->telegramBot->stopProcessingCommand($fromId);

                return $command;
            }
        }

        $processingCommand = $this->telegramBot->getProcessingCommand($fromId);

        if ($processingCommand !== null) {
            return $processingCommand;
        }

        return null;
    }

    /**
     * @throws \Exception
     */
    private function prepareTelegramData(TelegramRequest $telegramRequest): TelegramDto
    {
        return new TelegramDto(
            $this->userExtractor->extract($telegramRequest),
            $this->messageExtractor->extract($telegramRequest),
            $telegramRequest->getCallbackQuery()?->getData(),
        );
    }
}
