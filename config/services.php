<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use App\Telegram\Infrastructure\Command\SetWebHook;
use Longman\TelegramBot\Telegram;

return function (ContainerConfigurator $container): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services->load('App\\', '../src/')
        ->exclude('../src/{DependencyInjection,Entity,Kernel.php}');

    $services->set(Telegram::class)
        ->args([
            '$api_key' => env('TELEGRAM_API_KEY'),
            '$bot_username' => env('TELEGRAM_BOT_USERNAME'),
        ]);

    $services->set(SetWebHook::class)
        ->arg('$webhookUrl', env('TELEGRAM_WEBHOOK_URL'));
};
