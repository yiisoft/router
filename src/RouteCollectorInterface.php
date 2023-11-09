<?php

declare(strict_types=1);

namespace Yiisoft\Router;

interface RouteCollectorInterface
{
    /**
     * Add a route or a group of routes.
     */
    public function addRoute(Route|Group|RoutableInterface ...$routes): self;

    /**
     * Appends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed first.
     */
    public function middleware(array|callable|string ...$definition): self;

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed last.
     */
    public function prependMiddleware(array|callable|string ...$definition): self;

    /**
     * @return Group[]|Route[]|RoutableInterface[]
     */
    public function getItems(): array;

    /**
     * Returns middleware definitions.
     *
     * @return array[]|callable[]|string[]
     */
    public function getMiddlewares(): array;
}
