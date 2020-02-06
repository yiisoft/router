<?php

namespace Yiisoft\Router;

use Psr\Container\ContainerInterface;

interface RouteCollectorInterface
{
    /**
     * Add a route
     *
     * @param Route $route
     */
    public function addRoute(Route $route): void;

    /**
     * Add a group of routes
     *
     * ```php
     * $group = Group::create('/api', [
     *     Route::get('/users', function () {}),
     *     Route::get('/contacts', function () {}),
     * ])->addMiddleware($myMiddleware);
     * $router->addGroupInstance($group);
     * ```
     * @param Group $group a group to add
     */
    public function addGroup(Group $group): void;

    /**
     * Return a clone with container specified.
     * The container is be used to resolve dependencies in callback or action caller middleware.
     *
     * @param ContainerInterface $container container instance
     * @return RouteCollectorInterface
     */
    public function withContainer(ContainerInterface $container): self;

    /**
     * @return bool if there is container specified
     */
    public function hasContainer(): bool;
}
