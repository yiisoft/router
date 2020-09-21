<?php

use Yiisoft\Router\MiddlewareStack;
use Yiisoft\Router\MiddlewareStackInterface;
use Yiisoft\Router\FastRoute\UrlGenerator;
use Yiisoft\Router\Group;
use Yiisoft\Router\MiddlewareFactoryInterface;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\UrlGeneratorInterface;
use Yiisoft\Router\MiddlewareFactory;

return [
    MiddlewareStackInterface::class => Middlewarestack::class,
    MiddlewareFactoryInterface::class => MiddlewareFactory::class,
    RouteCollectorInterface::class => Group::create(),
    UrlGeneratorInterface::class => UrlGenerator::class,
];
