<?php

declare(strict_types=1);

namespace Yiisoft\Router;

final class RouteCollector implements RouteCollectorInterface
{
    /**
     * @var Group[]|Route[]
     */
    private array $items = [];
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

    /**
     * Appends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed first.
     *
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function middleware($middlewareDefinition): RouteCollectorInterface
    {
        array_unshift($this->middlewareDefinitions, $middlewareDefinition);
        return $this;
    }

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed last.
     *
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function prependMiddleware($middlewareDefinition): RouteCollectorInterface
    {
        $this->middlewareDefinitions[] = $middlewareDefinition;
        return $this;
    }

    /**
     * @return Group[]|Route[]
     */
    public function getItems(): array
    {
        return $this->items;
    }

    public function getMiddlewareDefinitions(): array
    {
        return $this->middlewareDefinitions;
    }
}
