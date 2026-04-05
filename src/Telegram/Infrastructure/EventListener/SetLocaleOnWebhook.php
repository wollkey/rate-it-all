<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\EventListener;

use App\Infrastructure\Locale\LocaleResolver;
use App\Telegram\BeginHandleWebHook;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Translation\LocaleSwitcher;

#[AsEventListener]
final readonly class SetLocaleOnWebhook
{
    public function __construct(
        private LocaleSwitcher $localeSwitcher,
        private LocaleResolver $localeResolver,
    ) {
    }

    public function __invoke(BeginHandleWebHook $event): void
    {
        $languageCode = $event->telegramInput->user->languageCode;
        $locale = $this->localeResolver->resolve($languageCode);

        $this->localeSwitcher->setLocale($locale);
    }
}
