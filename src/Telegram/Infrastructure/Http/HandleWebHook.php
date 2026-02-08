<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Http;

use App\Game\Domain\Repository\GameRepository;
use App\Game\Infrastructure\Telegram\Repository\TelegramPlayerRepository;
use App\Telegram\AsTelegramHandler;
use App\Telegram\Domain\Enum\ChatType;
use App\Telegram\Domain\Enum\EntityType;
use App\Telegram\Domain\Enum\InputType;
use App\Telegram\Domain\Event\BeginHandleWebHook;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\Domain\Service\MessageExtractor;
use App\Telegram\Domain\Service\UserExtractor;
use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use App\Telegram\TelegramInput;
use Phptg\BotApi\TelegramBotApi;
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
     * @var array<class-string, AsTelegramHandler>
     */
    private array $attributeCache;

    /** @var array<string, callable(TelegramInput):void> */
    private array $commandHandlers = [];

    /** @var array<string, callable(TelegramInput):void> */
    private array $gameStateHandlers = [];

    /** @var array<class-string, callable(TelegramInput):void> */
    private array $allHandlers = [];

    /**
     * @param iterable<callable> $telegramHandlers
     */
    public function __construct(
        #[AutowireIterator('app.telegram.handler')]
        private readonly iterable $telegramHandlers,
        private readonly ConversationStorage $conversation,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LoggerInterface $logger,
        private readonly MessageExtractor $messageExtractor,
        private readonly TelegramBotApi $telegramApi,
        private readonly UserExtractor $userExtractor,
        private readonly GameRepository $gameRepository,
        private readonly TelegramPlayerRepository $playerRepository,
    ) {
        $this->buildHandlersCache();
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
        $update = Update::fromJson($request->getContent());
        $telegramInput = $this->createTelegramInput($update);

        $this->eventDispatcher->dispatch(new BeginHandleWebHook($telegramInput));

        if (null === $handler = $this->resolveTelegramHandler($telegramInput)) {
            return;
        }

        if (!$this->isInputTypeAllowed($telegramInput, $handler::class)) {
            return;
        }

        $this->executeHandler($handler, $telegramInput);
    }

    /**
     * @return callable(TelegramInput):void|null
     */
    private function resolveTelegramHandler(TelegramInput $telegramInput): ?callable
    {
        $commandName = $this->extractCommandName($telegramInput);
        if (isset($this->commandHandlers[$commandName])) {
            $this->conversation->clear($telegramInput->message->chat->id);
            return $this->commandHandlers[$commandName];
        }

        $player = $this->playerRepository->find($telegramInput->message->chat->id);
        $game = $this->gameRepository->findActiveByPlayer($player);
        if ($game !== null) {
            $state = $game->getState()->value;
            if (isset($this->gameStateHandlers[$state])) {
                return $this->gameStateHandlers[$state];
            }
        }

        $conversationStep = $this->conversation->get($telegramInput->message->chat->id);
        if ($conversationStep !== null) {
            $this->conversation->clear($telegramInput->message->chat->id);

            return $this->allHandlers[$conversationStep->handler];
        }

        return null;
    }

    private function isInputTypeAllowed(TelegramInput $telegramInput, string $handlerFqcn): bool
    {
        $attribute = $this->attributeCache[$handlerFqcn] ?? null;

        $chatType = ChatType::tryFrom($telegramInput->message->chat->type);
        if ($chatType !== null && !in_array($chatType, $attribute->chatTypes)) {
            return false;
        }

        $inputType = $telegramInput->isCallback() ? InputType::Callback : InputType::Text;
        if (!in_array($inputType, $attribute->inputTypes, true)) {
            return false;
        }

        return true;
    }

    /**
     * @throws InvalidArgumentException
     */
    private function executeHandler(callable $handler, TelegramInput $telegramDto): void
    {
        try {
            $handler($telegramDto);
        } catch (TelegramException $exception) {
            $this->conversation->clear($telegramDto->message->chat->id);
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
     * @throws \Exception
     */
    private function createTelegramInput(Update $update): TelegramInput
    {
        $message = $this->messageExtractor->extract($update);
        $conversationStep = $this->conversation->get($message->chat->id);

        return new TelegramInput(
            user: $this->userExtractor->extract($update),
            message: $message,
            callbackQuery: $update->callbackQuery,
            conversationStep: $conversationStep,
        );
    }

    private function extractCommandName(TelegramInput $telegramInput): ?string
    {
        if ($telegramInput->isCallback()) {
            return $telegramInput->callbackQuery->data;
        }

        $text = $telegramInput->message->text;

        if ($text === null) {
            return null;
        }

        foreach ((array) $telegramInput->message->entities as $entity) {
            if ($entity->type === EntityType::BotCommand->value) {
                return substr($text, $entity->offset, $entity->length);
            }
        }

        return null;
    }

    private function buildHandlersCache(): void
    {
        foreach ($this->telegramHandlers as $handler) {
            $reflection = new \ReflectionClass($handler);
            $attributes = $reflection->getAttributes(AsTelegramHandler::class);

            if (empty($attributes)) {
                continue;
            }

            $attribute = $attributes[0]->newInstance();
            $fqcn = $handler::class;
            $this->attributeCache[$fqcn] = $attributes[0]->newInstance();

            $this->allHandlers[$fqcn] = $handler;

            if ($attribute->command !== null) {
                $this->commandHandlers[$attribute->command] = $handler;
            }

            if ($attribute->gameState !== null) {
                $this->gameStateHandlers[$attribute->gameState->value] = $handler;
            }
        }
    }
}
