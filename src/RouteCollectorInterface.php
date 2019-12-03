<?php


namespace Yiisoft\Router;

use Psr\Http\Server\MiddlewareInterface;

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
     * });
     * ```
     *
     * @param string $prefix
     * @param callable $callback
     */
    public function addGroup(string $prefix, callable $callback, MiddlewareInterface $middleware = null): void;
}
