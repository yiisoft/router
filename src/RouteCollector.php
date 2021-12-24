<?php

declare(strict_types=1);

namespace Yiisoft\Router;

final class RouteCollector implements RouteCollectorInterface
{
    /**
     * @var Group[]|Route[]
     */
    private array $items = [];

    /**
     * @var array[]|callable[]|string[]
     */
    private array $middlewareDefinitions = [];

    public function addRoute(Route $route): RouteCollectorInterface
    {
        $this->items[] = $route;
        return $this;
    }

    public function addGroup(Group $group): RouteCollectorInterface
    {
        $this->items[] = $group;
        return $this;
    }

    public function middleware($middlewareDefinition): RouteCollectorInterface
    {
        array_unshift($this->middlewareDefinitions, $middlewareDefinition);
        return $this;
    }

    public function prependMiddleware($middlewareDefinition): RouteCollectorInterface
    {
        $this->middlewareDefinitions[] = $middlewareDefinition;
        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getMiddlewareDefinitions(): array
    {
        return $this->middlewareDefinitions;
    }
}
