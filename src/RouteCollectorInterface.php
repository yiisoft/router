<?php

declare(strict_types=1);

namespace Yiisoft\Router;

interface RouteCollectorInterface
{
    /**
     * Add a route
     *
     * @param Route $route
     * @return self
     */
    public function addRoute(Route $route): self;

    /**
     * Add a group of routes
     *
     * ```php
     * $group = Group::create('/api', [
     *     Route::get('/users', function () {}),
     *     Route::get('/contacts', function () {}),
     * ])->addMiddleware($myMiddleware);
     * $router->addGroup($group);
     * ```
     * @param Group $group a group to add
     * @return self
     */
    public function addGroup(Group $group): self;

    /**
     * Return a clone with container specified.
     * The container is be used to resolve dependencies in callback or action caller middleware.
     *
     * @param MiddlewareDispatcher $dispatcher container instance
     * @return RouteCollectorInterface
     */
    public function withDispatcher(MiddlewareDispatcher $dispatcher): self;

    /**
     * @return bool if there is container specified
     */
    public function hasDispatcher(): bool;

    /**
     * @return Route|Group[]
     */
    public function getItems(): array;
}
