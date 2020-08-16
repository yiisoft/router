<?php

declare(strict_types=1);

namespace Yiisoft\Router\Interfaces;

interface MiddlewareAwareInterface
{
    /**
     * Add handler that should be invoked for the route when match happens to the stack.
     *
     * @param mixed $handler that will be added.
     * Handler type and format depend on the implementation.
     * @param bool $validate whether to validate handler before execution
     *
     * @return MiddlewareAwareInterface
     */
    public function middleware($handler, $validate = false): MiddlewareAwareInterface;

    /**
     * Add multiple handlers that should be invoked for the route when match happens to the stack.
     *
     * @param array $handlers that will be added.
     * Handler type and format depend on the implementation.
     * @param false $validate whether to validate handlers before execution
     *
     * @return MiddlewareAwareInterface
     */
    public function middlewares(iterable $handlers, $validate = false): MiddlewareAwareInterface;

    /**
     * Prepend handler that should be invoked for the route when match happens to the stack.
     *
     * @param mixed $handler that will be added.
     * Handler type and format depend on the implementation.
     * @param bool $validate whether to validate handler before execution
     *
     * @return MiddlewareAwareInterface
     */
    public function prependMiddleware($handler, $validate = false): MiddlewareAwareInterface;

    /**
     * Get the stack of handlers that should be invoked for the route when match happens.
     *
     * @return iterable stack of handlers.
     */
    public function getMiddlewares(): iterable;
}
