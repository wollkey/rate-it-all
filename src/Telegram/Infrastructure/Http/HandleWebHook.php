<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Http;

use App\Telegram\AsTelegramCommand;
use App\Telegram\ConversationalCommand;
use App\Telegram\ConversationStep;
use App\Telegram\Domain\Enum\EntityType;
use App\Telegram\Domain\Event\BeginHandleWebHook;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\Domain\Service\MessageExtractor;
use App\Telegram\Domain\Service\UserExtractor;
use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use App\Telegram\TelegramDto;
use Phptg\BotApi\TelegramBotApi;
use Phptg\BotApi\Type\Message;
use Phptg\BotApi\Type\Update\Update;
use Psr\Cache\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[AsController]
#[Route('/{_locale}/telegram/hook')]
final class HandleWebHook
{
    /**
     * @var array<class-string, AsTelegramCommand>
     */
    private array $attributeCache;

    /**
     * @var array<class-string, callable>
     */
    private array $commandsMap;

    /**
     * @param iterable<callable> $telegramCommands
     */
    public function __construct(
        #[AutowireIterator('app.telegram_bot.command')]
        private readonly iterable $telegramCommands,
        private readonly ConversationStorage $conversation,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly MessageExtractor $messageExtractor,
        private readonly TelegramBotApi $telegramApi,
        private readonly UserExtractor $userExtractor,
    ) {
        $this->buildAttributeCache();
    }

    public function __invoke(Request $request): Response
    {
        try {
            $this->tryHandleWebHook($request);
        } catch (\Throwable $throwable) {
            $this->logger->error($throwable);
        }

        return new Response('Ok');
    }

    /**
     * @throws \Exception
     * @throws InvalidArgumentException
     */
    private function tryHandleWebHook(Request $request): void
    {
        $update = Update::fromJson($request->getContent());
        $telegramDto = $this->createTelegramDto($update);

        $this->eventDispatcher->dispatch(new BeginHandleWebHook($telegramDto));

        try {
            $this->executeCommand($telegramDto);
        } catch (TelegramException $exception) {
            $this->logger->error(json_encode([
                'error' => $exception->getMessage(),
                'previous' => $exception->getPrevious()?->getMessage(),
                'trace' => $exception->getTrace(),
            ]));
            $exceptionMessage = !empty($exception->getMessage()) ? $exception->getMessage() : 'Unknown error';
            $this->telegramApi->sendMessage($telegramDto->user->id, $exceptionMessage);
        }
    }

    /**
     * @throws InvalidArgumentException
     */
    private function executeCommand(TelegramDto $telegramDto): void
    {
        $commandFqcn = $this->resolveCommandFromMessage($telegramDto);
        $chatId = $telegramDto->user->id;

        if (null !== $commandFqcn) {
            $this->conversation->clear($chatId);
            $this->runCommand($commandFqcn, $telegramDto);

            return;
        }

        $conversation = $this->conversation->get($chatId);

        if (null === $conversation) {
            return;
        }

        $this->runCommand($conversation['command'], $telegramDto, $conversation['step']);
    }

    /**
     * @param class-string $commandFqcn
     *
     * @throws InvalidArgumentException
     */
    private function runCommand(string $commandFqcn, TelegramDto $telegramDto, ?ConversationStep $step = null): void
    {
        $chatId = $telegramDto->user->id;
        $command = $this->commandsMap[$commandFqcn] ?? null;

        if (null === $command) {
            $this->conversation->clear($chatId);

            return;
        }

        if ($command instanceof ConversationalCommand) {
            $nextStep = $command($telegramDto, $step);
            null !== $nextStep
                ? $this->conversation->save($chatId, $commandFqcn, $nextStep)
                : $this->conversation->clear($chatId);

            return;
        }

        $command($telegramDto);
        $this->conversation->clear($telegramDto->user->id);
    }

    /**
     * @return class-string|null
     */
    private function resolveCommandFromMessage(TelegramDto $telegramDto): ?string
    {
        $textCommand = $this->extractCommand($telegramDto->message);

        return array_find_key(
            $this->attributeCache,
            fn ($attribute) => $this->isCommandMatched($attribute, $telegramDto, $textCommand)
        );
    }

    /**
     * @throws \Exception
     */
    private function createTelegramDto(Update $update): TelegramDto
    {
        return new TelegramDto(
            $this->userExtractor->extract($update),
            $this->messageExtractor->extract($update),
            $update->callbackQuery,
        );
    }

    private function extractCommand(Message $message): ?string
    {
        if (null === $message->text) {
            return null;
        }

        $entities = (array) $message->entities;
        foreach ($entities as $entity) {
            if ($entity->type === EntityType::BotCommand->value) {
                return substr($message->text, $entity->offset, $entity->length);
            }
        }

        return null;
    }

    private function isCommandMatched(
        AsTelegramCommand $attribute,
        TelegramDto $telegramDto,
        ?string $textCommand,
    ): bool {
        if (null !== $attribute->chatType && $telegramDto->message->chat->type !== $attribute->chatType->value) {
            return false;
        }

        if (null !== $textCommand && $attribute->command === $textCommand) {
            return true;
        }

        if ($attribute->supportReplyMarkup && $attribute->command === $telegramDto->callbackQuery?->data) {
            return true;
        }

        return false;
    }

    private function buildAttributeCache(): void
    {
        foreach ($this->telegramCommands as $command) {
            $reflection = new \ReflectionClass($command);
            $attributes = $reflection->getAttributes(AsTelegramCommand::class);

            if ([] === $attributes) {
                continue;
            }

            $this->attributeCache[$command::class] = $attributes[0]->newInstance();
            $this->commandsMap[$command::class] = $command;
        }
    }
}
