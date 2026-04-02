<?php

declare(strict_types=1);

namespace App\Tests\Common;

use App\Telegram\Domain\Service\MessageExtractor;
use App\Telegram\Domain\Service\UserExtractor;
use App\Telegram\TelegramInput;
use Phptg\BotApi\Type\Update\Update;

trait CreateTelegramInput
{
    private function createTextInput(string $text, bool $isCommand = false): TelegramInput
    {
        $entities = $isCommand ? [
            ['type' => 'bot_command', 'offset' => 0, 'length' => strlen($text)],
        ] : [];

        $update = Update::fromJson((string) json_encode([
            'update_id' => 1,
            'message' => [
                'message_id' => 1,
                'from' => ['id' => 1, 'first_name' => 'Alex', 'is_bot' => false],
                'chat' => ['id' => 1, 'type' => 'private'],
                'text' => $text,
                'date' => time(),
                'entities' => $entities,
            ],
        ]));

        return new TelegramInput(
            user: new UserExtractor()->extract($update),
            message: new MessageExtractor()->extract($update),
        );
    }

    private function createCallbackInput(string $data): TelegramInput
    {
        $update = Update::fromJson((string) json_encode([
            'update_id' => 1,
            'callback_query' => [
                'id' => 'abc',
                'chat_instance' => 'test',
                'from' => ['id' => 1, 'first_name' => 'Alex', 'is_bot' => false],
                'message' => [
                    'message_id' => 1,
                    'chat' => ['id' => 1, 'type' => 'private'],
                    'date' => time(),
                ],
                'data' => $data,
            ],
        ]));

        return new TelegramInput(
            user: new UserExtractor()->extract($update),
            message: new MessageExtractor()->extract($update),
            callbackQuery: $update->callbackQuery,
        );
    }
}
