<?php

declare(strict_types=1);

namespace Yiisoft\Router\Handler;

use Psr\Http\Server\MiddlewareInterface;

use function array_unshift;

trait HandlerAwareTrait
{
    private array $handlers = [];

    /**
     * Add handler that should be invoked for the route when match happens to the stack.
     *
     * @param mixed $handler that will be added.
     * Handler type and format support depends on the \Yiisoft\Router\Dispatcher\DispatcherInterface implementation.
     * @param bool $validate whether to validate handler before execution
     *
     * @return HandlerAwareInterface
     */
    public function handler($handler, $validate = false): HandlerAwareInterface
    {
        $this->handlers[] = $handler;

        return $this;
    }

    /**
     * Add multiple handlers that should be invoked for the route when match happens to the stack.
     *
     * @param array $handlers that will be added.
     * Handler type and format support depends on the \Yiisoft\Router\Dispatcher\DispatcherInterface implementation.
     * @param false $validate whether to validate handlers before execution
     *
     * @return HandlerAwareInterface
     */
    public function handlers(iterable $handlers, $validate = false): HandlerAwareInterface
    {
        foreach ($handlers as $handler) {
            $this->handler($handler, $validate);
        }

        return $this;
    }

    /**
     * Prepend handler that should be invoked for the route when match happens to the stack.
     *
     * @param mixed $handler that will be added.
     * Handler type and format support depends on the \Yiisoft\Router\Dispatcher\DispatcherInterface implementation.
     * @param bool $validate whether to validate handler before execution
     *
     * @return HandlerAwareInterface
     */
    public function prependHandler($handler, $validate = false): HandlerAwareInterface
    {
        array_unshift($this->handlers, $handler);

        return $this;
    }

    /**
     * Get the stack of handlers that should be invoked for the route when match happens.
     *
     * @return iterable stack of handlers.
     */
    public function getHandlers(): iterable
    {
        return $this->handlers;
    }
}
