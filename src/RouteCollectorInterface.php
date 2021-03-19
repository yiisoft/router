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
     * $group = Group::create('/api', [
     *     Route::get('/users', function () {}),
     *     Route::get('/contacts', function () {}),
     * ])->addMiddleware($myMiddleware);
     * $router->addGroup($group);
     * ```
     *
     * @param Group $group a group to add
     *
     * @return self
     */
    public function addGroup(Group $group): self;

    /**
     * @return Group[]|Route
     */
    public function getItems(): array;
}
