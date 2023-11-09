<?php

declare(strict_types=1);

namespace Yiisoft\Router;

final class RouteCollector implements RouteCollectorInterface
{
    /**
     * @var Group[]|Route[]|RoutableInterface[]
     */
    private array $items = [];

    /**
     * @var array[]|callable[]|string[]
     */
    private array $middlewares = [];

    public function addRoute(Route|Group|RoutableInterface ...$routes): RouteCollectorInterface
    {
        array_push(
            $this->items,
            ...array_values($routes)
        );
        return $this;
    }

    public function middleware(array|callable|string ...$definition): RouteCollectorInterface
    {
        array_push(
            $this->middlewares,
            ...array_values($definition)
        );
        return $this;
    }

    public function prependMiddleware(array|callable|string ...$definition): RouteCollectorInterface
    {
        array_unshift(
            $this->middlewares,
            ...array_values($definition)
        );
        return $this;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
