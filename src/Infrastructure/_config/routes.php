<?php

declare(strict_types=1);

use App\Infrastructure\Http\SentryTestController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import(SentryTestController::class, 'attribute');
};
