<?php

declare(strict_types=1);

namespace Yiisoft\Router\Route;

use Yiisoft\Router\Dispatcher\DispatcherAwareTrait;
use Yiisoft\Router\Handler\HandlerAwareTrait;

final class RouteCollection implements RouteCollectionInterface
{
    use DispatcherAwareTrait;
    use HandlerAwareTrait;

    private array $routes;

    public function __construct($routes = [])
    {
        $this->routes = $routes;
    }

    public function addRoute(RouteInterface $route): self
    {
        $new = clone $this;
        $new->routes[] = $route;

        return $new;
    }

    public function addRoutes(array $routes): self
    {
        // TODO: Implement addRoutes() method.
    }

    public function addCollection(RouteCollectionInterface $collection): self
    {
        // TODO: Implement addCollection() method.
    }

    public function getRoutes(): iterable
    {
        return $this->routes;
    }
}
