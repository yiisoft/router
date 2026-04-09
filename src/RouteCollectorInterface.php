<?php

declare(strict_types=1);

namespace Yiisoft\Router;

/**
 * Interface for route collectors that manage route registration.
 *
 * @deprecated Will be removed in the next major release.
 */
interface RouteCollectorInterface
{
    /**
     * Add a route or a group of routes.
     */
    public function addRoute(Route|Group ...$routes): self;

    /**
     * Appends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed first.
     *
     * @param array|callable|string ...$middlewareDefinition Middleware definitions.
     * @return self New instance with the middleware appended.
     */
    public function middleware(array|callable|string ...$middlewareDefinition): self;

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed last.
     *
     * @param array|callable|string ...$middlewareDefinition Middleware definitions.
     * @return self New instance with the middleware prepended.
     */
    public function prependMiddleware(array|callable|string ...$middlewareDefinition): self;

    /**
     * Returns all registered items (routes and groups).
     *
     * @return Group[]|Route[]
     */
    public function getItems(): array;

    /**
     * Returns all middleware definitions.
     *
     * @return array[]|callable[]|string[]
     */
    public function getMiddlewareDefinitions(): array;
}
