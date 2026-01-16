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

    public function addRoute(Route|Group ...$routes): RouteCollectorInterface
    {
        array_push(
            $this->items,
            ...array_values($routes),
        );
        return $this;
    }

    public function middleware(array|callable|string ...$middlewareDefinition): RouteCollectorInterface
    {
        array_push(
            $this->middlewareDefinitions,
            ...array_values($middlewareDefinition),
        );
        return $this;
    }

    public function prependMiddleware(array|callable|string ...$middlewareDefinition): RouteCollectorInterface
    {
        array_unshift(
            $this->middlewareDefinitions,
            ...array_values($middlewareDefinition),
        );
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
