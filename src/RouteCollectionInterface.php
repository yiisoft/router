<?php

declare(strict_types=1);

namespace Yiisoft\Router;

/**
 * Interface for route collections that provide access to registered routes.
 */
interface RouteCollectionInterface
{
    /**
     * Returns all routes in the collection.
     *
     * @return Route[] Array of routes indexed by name.
     */
    public function getRoutes(): array;

    /**
     * Returns a route by name.
     *
     * @param string $name Route name.
     * @return Route The route instance.
     * @throws RouteNotFoundException If the route is not found.
     */
    public function getRoute(string $name): Route;

    /**
     * Returns routes tree array.
     *
     * @return array Hierarchical array of routes and/or groups.
     */
    public function getRouteTree(): array;
}
