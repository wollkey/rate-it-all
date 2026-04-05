<?php

declare(strict_types=1);

use App\Infrastructure\Locale\LocaleResolver;
use Monolog\Level;
use Sentry\SentryBundle\Monolog\LogsHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services->set(LocaleResolver::class);

    if ($container->env() === 'prod') {
        $services->set(LogsHandler::class)
            ->args([Level::Info]);
    }
};
