<?php

declare(strict_types=1);

use App\Telegram\AsTelegramHandler;
use App\Telegram\Infrastructure\Handler\ChainHandlerResolver;
use App\Telegram\Infrastructure\Handler\HandlerResolver;
use App\Telegram\OnCommand;
use Phptg\BotApi\TelegramBotApi;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (ContainerConfigurator $container, ContainerBuilder $builder): void {
    $services = $container->services()
        ->defaults()
            ->autowire()
            ->autoconfigure();

    $services
        ->load('App\\Telegram\\', '../*')
        ->exclude('../{_config}');

    $services->set(TelegramBotApi::class)
        ->args(['$token' => env('TELEGRAM_API_KEY')]);

    $services->set(ChainHandlerResolver::class)
        ->alias(HandlerResolver::class, ChainHandlerResolver::class);

    $builder->registerAttributeForAutoconfiguration(
        OnCommand::class,
        static function (ChildDefinition $definition, OnCommand $attr): void {
            $definition->addTag('app.telegram.command_handler', [
                'key' => $attr->command,
            ]);
        },
    );

    $builder->registerAttributeForAutoconfiguration(
        AsTelegramHandler::class,
        static function (ChildDefinition $definition): void {
            $definition->addTag('app.telegram.handler');
        },
    );
};
