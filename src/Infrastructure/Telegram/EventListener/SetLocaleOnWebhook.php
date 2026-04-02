<?php

declare(strict_types=1);

namespace App\Infrastructure\Telegram\EventListener;

use App\Telegram\Domain\Event\BeginHandleWebHook;
use Symfony\Component\HttpFoundation\RequestStack;

final readonly class SetLocaleOnWebhook
{
    public function __construct(
        private RequestStack $requestStack,
        private string $defaultLocale = 'en',
    ) {
    }

    public function __invoke(BeginHandleWebHook $event): void
    {
        $languageCode = $event->telegramInput->user->languageCode;
        $locale = in_array($languageCode, ['ru', 'en'], true)
            ? $languageCode
            : $this->defaultLocale;

        $this->requestStack->getCurrentRequest()?->setLocale($locale);
    }
}
