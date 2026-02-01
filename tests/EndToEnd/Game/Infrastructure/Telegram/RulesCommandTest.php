<?php

declare(strict_types=1);

namespace App\Tests\EndToEnd\Game\Infrastructure\Telegram;

use App\Game\Infrastructure\Telegram\Command\AddThingCommand;
use App\Telegram\TelegramInput;
use App\Tests\Trait\FileAwareTrait;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

class RulesCommandTest extends KernelTestCase
{
    use FileAwareTrait;

    public function testSuccessExecute(From $user): void
    {
        /** @var AddThingCommand $addThingCommand */
        $addThingCommand = $this->getContainer()->get(AddThingCommand::class);
        /** @var SerializerInterface $serializer */
        $serializer = $this->getContainer()->get(SerializerInterface::class);

        $request = $this->read('Telegram/get_rules_valid_request.json');

        $addThingCommand->execute($serializer->deserialize($request, TelegramInput::class, JsonEncoder::FORMAT));
    }
}
