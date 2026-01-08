<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Shared\Application\LocaleSubscriber;
use App\Telegram\Infrastructure\Command\SetWebHook;
use Monolog\Processor\PsrLogMessageProcessor;
use Phptg\BotApi\TelegramBotApi;

return function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->bind('$telegramBotName', env('TELEGRAM_BOT_USERNAME'))
            ->autowire()
            ->autoconfigure();

    $services->load('App\\', '../src/')
        ->exclude('../src/{DependencyInjection,Entity,Kernel.php}');

    $services->set(TelegramBotApi::class)
        ->args([
            '$token' => env('TELEGRAM_API_KEY'),
        ]);

    $services->set(SetWebHook::class)
        ->arg('$webhookUrl', env('TELEGRAM_WEBHOOK_URL'));

    if ($container->env() === 'prod') {
        $services->set(PsrLogMessageProcessor::class)
            ->tag('monolog.processor', ['handler' => 'sentry']);
    }

    $services->set(LocaleSubscriber::class)
        ->arg('$defaultLocale', '%kernel.default_locale%');
};
