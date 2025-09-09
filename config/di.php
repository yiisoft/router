<?php

declare(strict_types=1);

use Yiisoft\Definitions\Reference;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\MethodFailureAction;
use Yiisoft\Router\Middleware\Router;

return [
    RouteCollectorInterface::class => RouteCollector::class,
    CurrentRoute::class => [
        'reset' => function () {
            $this->route = null;
            $this->uri = null;
            $this->arguments = [];
        },
    ],
    Router::class => [
        '__construct()' => [
            'methodFailureHandler' => Reference::to(MethodFailureAction::class),
        ],
    ],
];
