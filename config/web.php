<?php

use Yiisoft\Router\Group;
use Yiisoft\Router\MiddlewareFactory;
use Yiisoft\Router\MiddlewareFactoryInterface;
use Yiisoft\Router\MiddlewareStack;
use Yiisoft\Router\MiddlewareStackInterface;
use Yiisoft\Router\RouteCollectorInterface;

return [
    MiddlewareStackInterface::class => MiddlewareStack::class,
    MiddlewareFactoryInterface::class => MiddlewareFactory::class,
    RouteCollectorInterface::class => Group::create(),
];
