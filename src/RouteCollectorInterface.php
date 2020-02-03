<?php

namespace Yiisoft\Router;

interface RouteCollectorInterface
{
    /**
     * Add a route
     *
     * @param Route $route
     */
    public function addRoute(Route $route): void;

    /**
     * Add a prefix for a group of routes
     *
     * ```php
     * $router->addGroup('/api', function (Group $group) {
     *     $group->addRoute(Route::get('/users', function () {}));
     *     $group->addGroup(Route::get('/contacts', function () {}));
     *     $group->addMiddleware($myMiddleware);
     * });
     * ```
     *
     * @param string $prefix
     * @param callable $callback
     */
    public function addGroup(string $prefix, callable $callback): void;

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
    public function addGroupInstance(Group $group): void;
}
