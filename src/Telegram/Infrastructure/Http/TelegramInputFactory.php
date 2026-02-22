<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Http;

use App\Telegram\Domain\Service\MessageExtractor;
use App\Telegram\Domain\Service\UserExtractor;
use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use App\Telegram\TelegramInput;
use Phptg\BotApi\Type\Update\Update;
use Symfony\Component\HttpFoundation\Request;

final readonly class TelegramInputFactory
{
    public function __construct(
        private UserExtractor $userExtractor,
        private MessageExtractor $messageExtractor,
        private ConversationStorage $conversation,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function createFromRequest(Request $request): TelegramInput
    {
        $update = Update::fromJson($request->getContent());
        $message = $this->messageExtractor->extract($update);
        $conversationStep = $this->conversation->get($message->chat->id);

        return new TelegramInput(
            user: $this->userExtractor->extract($update),
            message: $message,
            callbackQuery: $update->callbackQuery,
            conversationStep: $conversationStep,
        );
    }
}
