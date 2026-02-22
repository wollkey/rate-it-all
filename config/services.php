<?php

declare(strict_types=1);

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

return static function (ContainerConfigurator $container, string $env): void {
    $container->import('../src/**/{_config}/{di}.{php,yaml}');
    $container->import("../src/**/{_config}/{di}_$env.{php,yaml}");
};
