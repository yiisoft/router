<?php

declare(strict_types=1);

use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\Router;
use Yiisoft\Router\RouterInterface;

return [
    RouteCollectorInterface::class => RouteCollector::class,
    RouterInterface::class => Router::class,
];
