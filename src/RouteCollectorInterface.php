<?php

declare(strict_types=1);

namespace Yiisoft\Router;

interface RouteCollectorInterface
{
    /**
     * Add a route or a group of routes.
     */
    public function addRoute(Route|Group ...$item): self;

    /**
     * Appends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed first.
     */
    public function middleware(array|callable|string ...$middlewareDefinition): self;

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed last.
     */
    public function prependMiddleware(array|callable|string ...$middlewareDefinition): self;

    /**
     * @return Group[]|Route[]
     */
    public function getItems(): array;

    /**
     * @return array[]|callable[]|string[]
     */
    public function getMiddlewareDefinitions(): array;
}
