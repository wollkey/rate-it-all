<?php

declare(strict_types=1);

use App\Game\Domain\Repository\GameRepository;
use App\Game\Domain\Repository\PlayerRepository;
use App\Game\Infrastructure\Orm\DoctrineGameRepository;
use App\Game\Infrastructure\Telegram\Handler\Resolver\OnGameState;
use App\Game\Infrastructure\Telegram\Repository\TelegramPlayerRepository;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

use function Symfony\Component\DependencyInjection\Loader\Configurator\env;

return static function (ContainerConfigurator $container, ContainerBuilder $builder): void {
    $services = $container->services()
        ->defaults()
            ->bind('$telegramBotName', env('TELEGRAM_BOT_USERNAME'))
            ->autowire()
            ->autoconfigure();

    $services
        ->load('App\\Game\\', '../*')
        ->exclude('../{_config}');

    $services->alias(PlayerRepository::class, TelegramPlayerRepository::class);
    $services->alias(GameRepository::class, DoctrineGameRepository::class);

    $builder->registerAttributeForAutoconfiguration(
        OnGameState::class,
        static function (ChildDefinition $definition, OnGameState $attr): void {
            $definition->addTag('app.game.state_handler', [
                'key' => $attr->state->value,
            ]);
        },
    );
};
