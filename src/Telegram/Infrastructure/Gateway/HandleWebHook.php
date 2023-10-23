<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Gateway;

use App\Telegram\Application\Dto\TelegramDto;
use App\Telegram\Domain\Entity\From;
use App\Telegram\Domain\Entity\TelegramRequest;
use App\Telegram\Domain\EntityExtractor\MessageExtractor;
use App\Telegram\Domain\EntityExtractor\UserExtractor;
use App\Telegram\Domain\Event\BeginHandleWebHook;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\Domain\TelegramBot;
use App\Telegram\Infrastructure\Contract\BotCommandInterface;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsController]
#[Route('/telegram/hook')]
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
    ) {
    }

    /**
     * @throws \Exception
     */
    public function __invoke(#[MapRequestPayload] TelegramRequest $telegramRequest): Response
    {
        $telegramDto = $this->prepareTelegramData($telegramRequest);

        $this->eventDispatcher->dispatch(new BeginHandleWebHook($telegramDto));

        try {
            $command = $this->resolveCommand($telegramDto);

            $command?->execute($telegramDto);
        } catch (TelegramException $exception) {
            $this->telegramApi->sendMessage($telegramDto->getUser()->getId(), $exception->getMessage());
        }

        return new Response('Ok');
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
