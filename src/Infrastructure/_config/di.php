<?php

declare(strict_types=1);

use App\Infrastructure\Application\LocaleSubscriber;
use Monolog\Level;
use Monolog\Processor\PsrLogMessageProcessor;
use Sentry\SentryBundle\Monolog\LogsHandler;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services->set(LocaleSubscriber::class)
        ->arg('$defaultLocale', '%kernel.default_locale%');

    if ($container->env() === 'prod') {
        $services->set(PsrLogMessageProcessor::class)
            ->tag('monolog.processor', ['handler' => 'sentry']);

        $services->set(LogsHandler::class)
            ->args([Level::Warning]);
    }
};
