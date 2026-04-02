<?php

declare(strict_types=1);

namespace App\Tests\Telegram\Infrastructure\Http;

use App\Telegram\ConversationStep;
use App\Telegram\Domain\Service\MessageExtractor;
use App\Telegram\Domain\Service\UserExtractor;
use App\Telegram\Infrastructure\Conversation\ConversationStorage;
use App\Telegram\Infrastructure\Http\TelegramInputFactory;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;

final class TelegramInputFactoryTest extends TestCase
{
    private TelegramInputFactory $factory;

    protected function setUp(): void
    {
        $this->factory = new TelegramInputFactory(
            new UserExtractor(),
            new MessageExtractor(),
            new ConversationStorage(new ArrayAdapter()),
        );
    }

    public function testCreateFromTextQueryRequest(): void
    {
        $request = $this->createRequest([
            'update_id' => 1,
            'message' => [
                'message_id' => 42,
                'from' => ['id' => 1, 'first_name' => 'Alex', 'is_bot' => false],
                'chat' => ['id' => 1, 'type' => 'private'],
                'text' => 'Hello',
                'date' => time(),
            ],
        ]);

        $input = $this->factory->createFromRequest($request);

        self::assertSame(1, $input->user->id);
        self::assertSame(42, $input->message->messageId);
        self::assertNull($input->callbackQuery);
        self::assertNull($input->conversationStep);
    }

    public function testCreateFromCallbackQueryRequest(): void
    {
        $request = $this->createRequest([
            'update_id' => 1,
            'callback_query' => [
                'id' => 'abc',
                'chat_instance' => 'test_instance',
                'from' => ['id' => 1, 'first_name' => 'Alex', 'is_bot' => false],
                'message' => [
                    'message_id' => 42,
                    'chat' => ['id' => 1, 'type' => 'private'],
                    'date' => time(),
                ],
                'data' => '5',
            ],
        ]);

        $input = $this->factory->createFromRequest($request);

        self::assertSame(1, $input->user->id);
        self::assertTrue($input->isCallback());
        self::assertSame('abc', $input->callbackQuery->id);
    }

    public function testCreateWithExistingConversationStep(): void
    {
        $storage = new ConversationStorage(new ArrayAdapter());
        $storage->save(1, \stdClass::class, 'step1');

        $factory = new TelegramInputFactory(
            new UserExtractor(),
            new MessageExtractor(),
            $storage,
        );

        $request = $this->createRequest([
            'update_id' => 1,
            'message' => [
                'message_id' => 42,
                'from' => ['id' => 1, 'first_name' => 'Alex', 'is_bot' => false],
                'chat' => ['id' => 1, 'type' => 'private'],
                'text' => 'Hello',
                'date' => time(),
            ],
        ]);

        $input = $factory->createFromRequest($request);

        self::assertInstanceOf(ConversationStep::class, $input->conversationStep);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createRequest(array $payload): Request
    {
        $content = json_encode($payload);
        self::assertIsString($content);

        return Request::create(uri: '/', method: 'POST', content: $content);
    }
}
