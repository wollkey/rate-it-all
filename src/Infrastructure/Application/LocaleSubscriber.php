<?php

declare(strict_types=1);

namespace App\Infrastructure\Application;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

#[AsEventListener(event: KernelEvents::REQUEST, priority: 42)]
final readonly class LocaleSubscriber
{
    public function __construct(
        private string $defaultLocale = 'en',
    ) {
    }

    public function __invoke(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$request->hasPreviousSession()) {
            return;
        }

        $locale = $request->attributes->get('_locale');

        if (!is_string($locale) || $locale === '') {
            $request->getSession()->set('_locale', $locale);
        } else {
            $locale = $request->getSession()->get('_locale', $this->defaultLocale);

            $request->setLocale(is_string($locale) ? $locale : $this->defaultLocale);
        }
    }
}
