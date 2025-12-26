<?php

declare(strict_types=1);

namespace App\Tests\Trait;

trait FileAwareTrait
{
    private function read(string $filename): string
    {
        return file_get_contents($this->getFileFixturePath($filename));
    }

    private function getFileFixturePath(string $filename): string
    {
        return self::getContainer()->getParameter('kernel.project_dir')."/tests/Fixture/$filename";
    }
}
