<?php

declare(strict_types=1);

namespace App\Game\Infrastructure\Telegram\EventListener;

use App\Game\Domain\Entity\Player;
use App\Game\Domain\Repository\PlayerRepository;
use App\Infrastructure\Locale\LocaleResolver;
use App\Telegram\Domain\Event\BeginHandleWebHook;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;

#[AsEventListener]
final readonly class SyncPlayerOnWebhook
{
    public function __construct(
        private PlayerRepository $playerRepository,
        private EntityManagerInterface $entityManager,
        private LocaleResolver $localeResolver,
    ) {
    }

    public function __invoke(BeginHandleWebHook $event): void
    {
        $telegramUser = $event->telegramInput->user;
        $player = $this->playerRepository->findById($telegramUser->id);
        $locale = $this->localeResolver->resolve($telegramUser->languageCode);

        if ($player === null) {
            $this->entityManager->persist(new Player(
                telegramId: $telegramUser->id,
                firstName: $telegramUser->firstName,
                locale: $locale,
                lastName: $telegramUser->lastName,
            ));
        } else {
            $player->updateProfile($telegramUser->firstName, $telegramUser->lastName);

            if ($player->getLocale() !== $locale) {
                $player->changeLocale($locale);
            }
        }

        $this->entityManager->flush();
    }
}
