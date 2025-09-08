<?php

declare(strict_types=1);

use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\MethodFailureHandlerInterface;
use Yiisoft\Router\MethodFailureHandler;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\RouteCollectorInterface;

return [
    RouteCollectorInterface::class => RouteCollector::class,
    CurrentRoute::class => [
        'reset' => function () {
            $this->route = null;
            $this->uri = null;
            $this->arguments = [];
        },
    ],
];
