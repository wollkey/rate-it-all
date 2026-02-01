<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\DependencyInjection;

use App\Telegram\AsTelegramHandler;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class TelegramAutoconfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            AsTelegramHandler::class,
            static function (ChildDefinition $definition): void {
                $definition->addTag('app.telegram.handler');
            }
        );
    }
}
