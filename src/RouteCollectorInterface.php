<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

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

    public function withDispatcher(MiddlewareDispatcher $dispatcher): self;

    public function hasDispatcher(): bool;

    public function middleware($middlewareDefinition): self;

    public function prependMiddleware($middlewareDefinition): self;

    public function disableMiddleware($middlewareDefinition): self;

    public function host(string $host): self;

    public function namePrefix(string $namePrefix): self;

    public function routes(...$routes): self;
}
