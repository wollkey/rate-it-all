<?php

declare(strict_types=1);

$finder = new PhpCsFixer\Finder()->in(__DIR__);

return new PhpCsFixer\Config()
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        '@PHP8x4Migration' => true,
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/var/.php-cs-fixer.cache');
