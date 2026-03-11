<?php

declare(strict_types=1);

use App\Infrastructure\Application\LocaleSubscriber;
use App\Infrastructure\Http\SentryTestController;
use Monolog\Level;
use Sentry\SentryBundle\Monolog\LogsHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services->set(LocaleSubscriber::class)
        ->arg('$defaultLocale', '%kernel.default_locale%');

    $services->set(SentryTestController::class);

    if ($container->env() === 'prod') {
        $services->set(LogsHandler::class)
            ->args([Level::Info]);
    }
};
