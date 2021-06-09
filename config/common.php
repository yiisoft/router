<?php

declare(strict_types=1);

use \Yiisoft\Middleware\Dispatcher\MiddlewareDispatcherInterface;
use \Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Router\Group;
use Yiisoft\Router\RouteCollectorInterface;

return [
    MiddlewareDispatcherInterface::class => MiddlewareDispatcher::class,
    RouteCollectorInterface::class => Group::create(),
];
