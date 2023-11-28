<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\Gateway;

use App\Telegram\Domain\Entity\Message;
use App\Telegram\Domain\Entity\TelegramResponse;
use App\Telegram\Domain\Exception\TelegramException;
use App\Telegram\Infrastructure\Contract\TelegramApiInterface;
use Longman\TelegramBot\Entities\Message as LongmanMessage;
use Longman\TelegramBot\Exception\TelegramException as ExternalTelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Telegram;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class TelegramApi implements TelegramApiInterface
{
    public function __construct(
        private Telegram $telegram,
        private SerializerInterface $serializer,
    ) {
    }

    /**
     * @throws \Exception
     */
    public function setWebHook(string $url): void
    {
        try {
            $this->telegram->setWebhook($url);
        } catch (ExternalTelegramException $exception) {
            throw new TelegramException("Webhook was not set because of error {$exception->getMessage()}");
        }
    }

    /**
     * @throws \Exception
     */
    public function sendMessage(int $chatId, string $text, array $data = []): TelegramResponse
    {
        try {
            $response = Request::sendMessage([
                'chat_id' => $chatId,
                'text' => $text,
                ...$data,
            ]);
        } catch (ExternalTelegramException $exception) {
            throw new TelegramException("Message was not sent because of error {$exception->getMessage()}");
        }

        if (!$response->isOk()) {
            throw new \Exception($response->printError(true));
        }

        return new TelegramResponse($this->mapTelegramMessage($response->getResult()));
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

    private function mapTelegramMessage(LongmanMessage $message): Message
    {
        $serializedMessage = $this->serializer->serialize($message, JsonEncoder::FORMAT);

        return $this->serializer->deserialize($serializedMessage, Message::class, JsonEncoder::FORMAT);
    }
}
