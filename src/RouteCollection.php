<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Router\Interfaces\RouteCollectionInterface;
use Yiisoft\Router\Interfaces\RouteInterface;

class RouteCollection implements RouteCollectionInterface
{
    use MiddlewareAwareTrait;
    use DispatcherAwareTrait;

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

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
