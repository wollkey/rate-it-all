<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Repository\PlayerRepository;
use App\Telegram\Domain\Event\BeginHandleWebHook;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class CreatePlayerOnWebhook
{
    public function __construct(
        private PlayerRepository $playerRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(BeginHandleWebHook $event): void
    {
        $user = $event->telegramDto->user;
        $player = $this->playerRepository->find($user->id);

        if (null !== $player) {
            return;
        }

        $player = new Player()
            ->setFirstName($user->firstName)
            ->setTelegramId($user->id);

        if (null !== $user->lastName) {
            $player->setLastName($user->lastName);
        }

        $this->entityManager->persist($player);
        $this->entityManager->flush();
    }
}
