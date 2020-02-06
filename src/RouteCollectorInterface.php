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
     *
     * @param Group $group
     */
    public function addGroup(Group $group): void;

    public function withContainer(ContainerInterface $container);

    public function hasContainer(): bool;
}
