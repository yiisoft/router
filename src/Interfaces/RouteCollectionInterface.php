<?php

declare(strict_types=1);

namespace Yiisoft\Router\Interfaces;

// TODO: extend \IteratorAggregate, \ArrayAccess, \Countable, \Serializable
interface RouteCollectionInterface extends MiddlewareAwareInterface, DispatcherAwareInterface
{
    public function addRoute(RouteInterface $route): RouteCollectionInterface;

    public function addRoutes(array $routes): RouteCollectionInterface;

    public function addCollection(RouteCollectionInterface $collection): RouteCollectionInterface;

    /**
     * @return RouterInterface[]
     */
    public function getRoutes(): array;
}
