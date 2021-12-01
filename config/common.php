<?php

declare(strict_types=1);

use Yiisoft\Router\CurrentRouteInterface;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\CurrentRoute;

return [
    RouteCollectorInterface::class => RouteCollector::class,
    CurrentRouteInterface::class => [
        'class' => CurrentRoute::class,
        'reset' => function () {
            $this->route = null;
            $this->uri = null;
            $this->arguments = [];
        },
    ],
];
