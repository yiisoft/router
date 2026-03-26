<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Router\Provider\RoutesProviderInterface;

final class RouteCollector implements RouteCollectorInterface
{
    /**
     * @var Group[]|Route[]
     */
    private array $items = [];

    /**
     * @var RoutesProviderInterface[]
     */
    private array $providers;

    /**
     * @var array[]|callable[]|string[]
     */
    private array $middlewareDefinitions = [];

    private bool $providersAreInjected = false;

    /**
     * @param RoutesProviderInterface[] $providers
     */
    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

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
        if (!$this->providersAreInjected) {
            foreach ($this->providers as $provider) {
                array_push(
                    $this->items,
                    ...$provider->getRoutes(),
                );
            }
            $this->providersAreInjected = true;
        }
        return $this->items;
    }

    public function getMiddlewareDefinitions(): array
    {
        return $this->middlewareDefinitions;
    }
}
