<?php

declare(strict_types=1);

namespace Yiisoft\Router\Route;

use Yiisoft\Router\Dispatcher\DispatcherAwareInterface;
use Yiisoft\Router\Handler\HandlerAwareInterface;

// TODO: extend \IteratorAggregate, \ArrayAccess, \Countable, \Serializable
interface RouteCollectionInterface extends HandlerAwareInterface, DispatcherAwareInterface
{
    public function addRoute(RouteInterface $route): RouteCollectionInterface;

    public function addRoutes(array $routes): RouteCollectionInterface;

    public function addCollection(RouteCollectionInterface $collection): RouteCollectionInterface;

    public function getRoutes(): iterable;
}
