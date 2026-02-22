<?php

declare(strict_types=1);

use App\Telegram\Infrastructure\Http\WebHookController;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

return static function (RoutingConfigurator $routes): void {
    $routes->import(WebHookController::class, 'attribute');
};
