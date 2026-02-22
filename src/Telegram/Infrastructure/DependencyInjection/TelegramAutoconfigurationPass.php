<?php

declare(strict_types=1);

namespace App\Telegram\Infrastructure\DependencyInjection;

use App\Telegram\AsTelegramHandler;
use App\Telegram\Infrastructure\Handler\CommandHandlerResolver;
use App\Telegram\Infrastructure\Handler\ConversationHandlerResolver;
use Symfony\Component\DependencyInjection\ChildDefinition;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

final class TelegramAutoconfigurationPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(CommandHandlerResolver::class)) {
            return;
        }

        $handlers = [];
        $metadata = [];

        foreach ($container->findTaggedServiceIds('telegram.handler') as $id => $tags) {
            foreach ($tags as $attrs) {
                $command = $attrs['command'];
                $handlers[$command] = new Reference($id);
                $metadata[$command] = [
                    'inputTypes' => array_map(
                        static fn (string $v) => InputType::from($v),
                        explode(',', $attrs['input_types']),
                    ),
                    'chatTypes' => array_map(
                        static fn (string $v) => ChatType::from($v),
                        explode(',', $attrs['chat_types']),
                    ),
                ];
            }
        }

        $container->getDefinition(CommandHandlerResolver::class)
            ->setArgument('$handlers', ServiceLocatorTagPass::register($container, $handlers))
            ->setArgument('$metadata', $metadata);

        $container->registerAttributeForAutoconfiguration(
            AsTelegramHandler::class,
            static function (ChildDefinition $definition): void {
                $definition->addTag('app.telegram.handler');
            }
        );

        $commandHandlers = [];
        $conversationHandlers = [];

        foreach ($container->findTaggedServiceIds('telegram.handler') as $serviceId => $_) {
            $definition = $container->getDefinition($serviceId);
            $class = $definition->getClass() ?? $serviceId;

            $reflection = new \ReflectionClass($class);
            $attributes = $reflection->getAttributes(AsTelegramHandler::class);

            if ($attributes === []) {
                continue;
            }

            /** @var AsTelegramHandler $attribute */
            $attribute = $attributes[0]->newInstance();
            $reference = new Reference($serviceId);

            if ($attribute->command !== null) {
                $commandHandlers[$attribute->command] = $reference;
                continue;
            }

            if ($attribute->context === []) {
                $conversationHandlers[$class] = $reference;
            }
        }

        $container->getDefinition(CommandHandlerResolver::class)
            ->setArgument('$handlers', $commandHandlers);

        $container->getDefinition(ConversationHandlerResolver::class)
            ->setArgument('$handlers', $conversationHandlers);
    }

    private function configureCommandResolver(ContainerBuilder $container): void
    {
    }
}
