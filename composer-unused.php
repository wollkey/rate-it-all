<?php

declare(strict_types=1);

use ComposerUnused\ComposerUnused\Configuration\Configuration;
use ComposerUnused\ComposerUnused\Configuration\ConfigurationSet\SymfonyConfigurationSet;
use ComposerUnused\ComposerUnused\Configuration\NamedFilter;

$composerJson = json_decode((string) file_get_contents(__DIR__ . '/composer.json'), true);
$rootPackageName = $composerJson['name'] ?? 'root';

return static function (Configuration $config) use ($rootPackageName): Configuration {
    $config->applyConfigurationSet(new SymfonyConfigurationSet($rootPackageName));

    $config->addNamedFilter(NamedFilter::fromString('symfony/flex'));
    $config->addNamedFilter(NamedFilter::fromString('symfony/runtime'));
    $config->addNamedFilter(NamedFilter::fromString('symfony/dotenv'));
    $config->addNamedFilter(NamedFilter::fromString('symfony/serializer'));
    $config->addNamedFilter(NamedFilter::fromString('symfony/property-info'));
    $config->addNamedFilter(NamedFilter::fromString('symfony/property-access'));
    $config->addNamedFilter(NamedFilter::fromString('phpdocumentor/reflection-docblock'));

    return $config;
};
