<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Router\Provider\RoutesProviderInterface;

/**
 * Simple route collector that manages routes, groups, and middleware definitions.
 */
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

    private bool $providersAreInjected = false;

    /**
     * @param RoutesProviderInterface[] $providers
     */
    public function __construct(private readonly array $providers = []) {}

    /**
     * Adds routes or groups to the collector.
     *
     * @param Route|Group ...$routes Routes or groups to add.
     * @return RouteCollectorInterface The collector instance.
     */
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

    /**
     * Returns all registered items (routes and groups).
     *
     * @return Group[]|Route[]
     */
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

    /**
     * Returns all middleware definitions.
     *
     * @return array[]|callable[]|string[]
     */
    public function getMiddlewareDefinitions(): array
    {
        return $this->middlewareDefinitions;
    }
}
