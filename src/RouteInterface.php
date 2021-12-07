<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

interface RouteInterface
{
    public function withDispatcher(MiddlewareDispatcher $dispatcher): self;

    public function name(string $name): self;

    public function pattern(string $pattern): self;

    public function host(string $host): self;

    /**
     * Marks route as override. When added it will replace existing route with the same name.
     *
     * @return self
     */
    public function override(): self;

    /**
     * Parameter default values indexed by parameter names.
     *
     * @param array $defaults
     *
     * @return self
     */
    public function defaults(array $defaults): self;

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
     * Last added handler will be executed first.
     *
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function prependMiddleware($middlewareDefinition): self;

    /**
     * Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     *
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function disableMiddleware($middlewareDefinition): self;

    /**
     * Appends action handler. It is a primary middleware definition that should be invoked last for a matched route.
     *
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function action($middlewareDefinition): self;

    /**
     * Marks route as pre-flight. When added it will add OPTIONS method to the route.
     *
     * @return self
     */
    public function preFlight(): self;
}
