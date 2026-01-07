<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\DependencyInjection;

use App\Telegram\AsTelegramCommand;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TelegramAutoconfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            AsTelegramCommand::class,
            static function (ChildDefinition $definition): void {
                $definition->addTag('app.telegram_bot.command');
            }
        );
    }
}
