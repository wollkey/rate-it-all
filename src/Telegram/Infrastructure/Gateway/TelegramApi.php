<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Gateway;

use App\Telegram\Domain\Entity\Message;
use App\Telegram\Domain\Entity\TelegramResponse;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\Infrastructure\Contract\TelegramApiInterface;
use Phptg\BotApi\FailResult;
use Phptg\BotApi\TelegramBotApi;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Phptg\BotApi\Type\Message as TgMessage;

final readonly class TelegramApi implements TelegramApiInterface
{
    public function __construct(
        private TelegramBotApi $telegram,
        private SerializerInterface $serializer,
    ) {
    }

    public function setWebHook(string $url): void
    {
        $response = $this->telegram->setWebhook($url);

        if ($response instanceof FailResult) {
            throw new TelegramException("Message was not sent because of error: {$response->response->body}");
        }
    }

    /**
     * @throws \Exception
     */
    public function sendMessage(int $chatId, string $text, array $data = []): TelegramResponse
    {
        $response = $this->telegram->sendMessage(
            chatId: $chatId,
            text: $text,
//                ...$data,
        );

        if ($response instanceof FailResult) {
            throw new TelegramException("Message was not sent because of error: {$response->response->body}");
        }

        return new TelegramResponse($this->mapTelegramMessage($response));
    }

    public function editMessage(int $chatId, int $messageId, string $text, array $data = []): void
    {
        $response = Request::editMessageText([
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $text,
            ...$data,
        ]);

        if (!$response->isOk()) {
            throw new \Exception($response->printError(true));
        }
    }

    private function mapTelegramMessage(TgMessage $message): Message
    {
        $serializedMessage = $this->serializer->serialize($message, JsonEncoder::FORMAT);

        return $this->serializer->deserialize($serializedMessage, Message::class, JsonEncoder::FORMAT);
    }
}
