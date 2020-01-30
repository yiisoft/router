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
     * $router->addGroup('/api', function (RouteCollectorInterface $router) {
     *     $router->addRoute(...);
     *     $router->addGroup(...);
     * })->addMiddleware($myMiddleware);
     * ```
     *
     * @param string $prefix
     * @param callable $callback
     */
    public function addGroup(string $prefix, callable $callback): void;
}
