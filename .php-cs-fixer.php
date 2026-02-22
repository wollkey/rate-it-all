<?php

declare(strict_types=1);

use PhpCsFixer\Runner\Parallel\ParallelConfigFactory;

$finder = new PhpCsFixer\Finder()
    ->in(__DIR__.'/src')
    ->in(__DIR__.'/tests')
    ->in(__DIR__.'/config')
        ->notPath('reference.php');

return new PhpCsFixer\Config()
    ->setParallelConfig(ParallelConfigFactory::detect())
    ->setRules([
        '@PSR12' => true,
        '@Symfony' => true,
        '@PHP8x4Migration' => true,
        'yoda_style' => [
            'equal' => false,
            'identical' => false,
        ]
    ])
    ->setFinder($finder)
    ->setCacheFile(__DIR__.'/var/.php-cs-fixer.cache');
