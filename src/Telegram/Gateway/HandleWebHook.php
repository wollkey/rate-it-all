<?php

declare(strict_types=1);

namespace App\Telegram\Gateway;

use App\Telegram\Dto\From;
use App\Telegram\Dto\TelegramResponse;
use App\Telegram\Exception\TelegramException;
use App\Telegram\Orm\TelegramUser;
use App\Telegram\Orm\TelegramUserRepository;
use App\Telegram\TelegramApi;
use App\Telegram\TelegramBot;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
#[Route('/telegram/hook')]
final readonly class HandleWebHook
{
    public function __construct(
        private TelegramBot $telegramApp,
        private TelegramApi $telegramApi,
        private TelegramUserRepository $userRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(#[MapRequestPayload] TelegramResponse $telegramResponse): Response
    {
        $telegramMessage = $telegramResponse->getMessage();

        $this->saveUserIfNotExist($telegramMessage->getFrom());

        try {
            $command = $this->telegramApp->isMessageCommand($telegramMessage)
                ? $this->telegramApp->getMessageCommand($telegramMessage)
                : $this->telegramApp->getProcessingCommand($telegramMessage->getChat()->getId());

            if ($command !== null) {
                $this->telegramApp->executeCommand($command, $telegramMessage);
            }
        } catch (TelegramException $exception) {
            $this->telegramApi->sendMessage($telegramMessage->getFrom()->getId(), $exception->getMessage());
        }

        return new Response('Ok');
    }

    private function saveUserIfNotExist(From $from): TelegramUser
    {
        $user = $this->userRepository->findOneBy(['telegramId' => $from->getId()]);

        if ($user !== null) {
            return $user;
        }

        $user = (new TelegramUser())
            ->setFirstName($from->getFirstName())
            ->setLastName($from->getLastName())
            ->setTelegramId($from->getId());

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
}
