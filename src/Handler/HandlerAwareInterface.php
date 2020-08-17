<?php

declare(strict_types=1);

namespace Yiisoft\Router\Handler;

interface HandlerAwareInterface
{
    /**
     * Add handler that should be invoked for the route when match happens to the stack.
     *
     * @param mixed $handler that will be added.
     * Handler type and format support depends on the \Yiisoft\Router\Dispatcher\DispatcherInterface implementation.
     * @param bool $validate whether to validate handler before execution
     *
     * @return static
     */
    public function handler($handler, $validate = false): HandlerAwareInterface;

    /**
     * Add multiple handlers that should be invoked for the route when match happens to the stack.
     *
     * @param array $handlers that will be added.
     * Handler type and format support depends on the \Yiisoft\Router\Dispatcher\DispatcherInterface implementation.
     * @param false $validate whether to validate handlers before execution
     *
     * @return static
     */
    public function handlers(iterable $handlers, $validate = false): HandlerAwareInterface;

    /**
     * Prepend handler that should be invoked for the route when match happens to the stack.
     *
     * @param mixed $handler that will be added.
     * Handler type and format support depends on the \Yiisoft\Router\Dispatcher\DispatcherInterface implementation.
     * @param bool $validate whether to validate handler before execution
     *
     * @return static
     */
    public function prependHandler($handler, $validate = false): HandlerAwareInterface;

    /**
     * Get the stack of handlers that should be invoked for the route when match happens.
     *
     * @return iterable stack of handlers.
     */
    public function getHandlers(): iterable;
}
