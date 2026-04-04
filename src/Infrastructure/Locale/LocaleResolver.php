<?php

declare(strict_types=1);

namespace App\Infrastructure\Locale;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class LocaleResolver
{
    /**
     * @param list<string> $supportedLocales
     */
    public function __construct(
        #[Autowire('%kernel.default_locale%')]
        private string $defaultLocale,
        #[Autowire('%kernel.enabled_locales%')]
        private array $supportedLocales,
    ) {
    }

    public function resolve(?string $languageCode): string
    {
        return in_array($languageCode, $this->supportedLocales, true)
            ? $languageCode
            : $this->defaultLocale;
    }
}
