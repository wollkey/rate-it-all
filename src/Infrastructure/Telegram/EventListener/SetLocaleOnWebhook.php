<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\EventListener;

use App\Telegram\Domain\Event\BeginHandleWebHook;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Translation\LocaleSwitcher;

#[AsEventListener]
final readonly class SetLocaleOnWebhook
{
    public function __construct(
        private LocaleSwitcher $localeSwitcher,
    ) {
    }

    public function __invoke(BeginHandleWebHook $event): void
    {
        $languageCode = $event->telegramInput->user->languageCode;
        $locale = in_array($languageCode, ['ru', 'en'], true)
            ? $languageCode
            : $this->localeSwitcher->getLocale();

        $this->localeSwitcher->setLocale($locale);
    }
}
