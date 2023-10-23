<?php

declare(strict_types=1);

namespace App\Game\Application\EventListener;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Repository\PlayerRepositoryInterface;
use App\Telegram\Domain\Event\BeginHandleWebHook;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class CreatePlayerEventListener
{
    public function __construct(
        private PlayerRepositoryInterface $playerRepository,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(BeginHandleWebHook $event): void
    {
        $user = $event->getTelegramDto()->getUser();
        $player = $this->playerRepository->find($user->getId());

        if ($player !== null) {
            return;
        }

        $player = (new Player())
            ->setFirstName($user->getFirstName())
            ->setTelegramId($user->getId());

        if ($user->getLastName() !== null) {
            $player->setLastName($user->getLastName());
        }

        $this->entityManager->persist($player);
        $this->entityManager->flush();
    }
}