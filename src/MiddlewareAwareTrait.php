<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Router\Interfaces\MiddlewareAwareInterface;

use function array_unshift;

trait MiddlewareAwareTrait
{
    private array $middlewares = [];

    /**
     * Add handler that should be invoked for the route when match happens to the stack.
     *
     * @param MiddlewareInterface|string|array|callable $handler that will be added.
     * Handler can be a PSR middleware, PSR middleware class name, handler action
     * (an array of [handlerClass, handlerMethod]) or a callable.
     * @param bool $validate whether to validate handler before execution
     *
     * @return MiddlewareAwareInterface
     */
    public function middleware($handler, $validate = false): MiddlewareAwareInterface
    {
        $this->middlewares[] = $handler;

        return $this;
    }

    /**
     * Add multiple handlers that should be invoked for the route when match happens to the stack.
     *
     * @param array $handlers that will be added.
     * Handler type and format depend on the implementation.
     * @param false $validate whether to validate handlers before execution
     *
     * @return MiddlewareInterface
     */
    public function middlewares(iterable $handlers, $validate = false): MiddlewareAwareInterface
    {
        foreach ($handlers as $handler) {
            $this->middleware($handler, $validate);
        }

        return $this;
    }

    /**
     * Prepend handler that should be invoked for the route when match happens to the stack.
     *
     * @param mixed $handler that will be added.
     * Handler type and format depend on the implementation.
     * @param bool $validate whether to validate handler before execution
     *
     * @return MiddlewareAwareInterface
     */
    public function prependMiddleware($handler, $validate = false): MiddlewareAwareInterface
    {
        array_unshift($this->middlewares, $handler);

        return $this;
    }

    /**
     * Get the stack of handlers that should be invoked for the route when match happens.
     *
     * @return iterable stack of handlers.
     */
    public function getMiddlewares(): iterable
    {
        return $this->middlewares;
    }
}
