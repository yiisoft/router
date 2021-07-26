<?php

declare(strict_types=1);

namespace Yiisoft\Router;

interface RouteCollectorInterface
{
    /**
     * Add a route
     *
     * @param Route $route
     *
     * @return self
     */
    public function addRoute(Route $route): self;

    /**
     * Add a group of routes
     *
     * ```php
     * $group = Group::create('/api')
     * ->middleware($myMiddleware)
     * ->routes(
     *     Route::get('/users', function () {}),
     *     Route::get('/contacts', function () {}),
     * );
     * $routeCollector->addGroup($group);
     * ```
     *
     * @param Group $group a group to add
     *
     * @return self
     */
    public function addGroup(Group $group): self;

    /**
     * Appends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed first.
     *
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function middleware($middlewareDefinition): self;

    /**
     * Prepends a handler middleware definition that should be invoked for a matched route.
     * First added handler will be executed last.
     *
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function prependMiddleware($middlewareDefinition): self;

    /**
     * @return Group[]|Route[]
     */
    public function getItems(): array;

    public function getMiddlewareDefinitions(): array;
}
