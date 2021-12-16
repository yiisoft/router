<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

interface GroupInterface
{
    public function withDispatcher(MiddlewareDispatcher $dispatcher): self;

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
     * Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     *
     * @param mixed $middlewareDefinition
     *
     * @return self
     */
    public function disableMiddleware($middlewareDefinition): self;

    public function host(string $host): self;

    public function namePrefix(string $namePrefix): self;

    public function routes(...$routes): self;

    public function withCors($middlewareDefinition): self;
}
