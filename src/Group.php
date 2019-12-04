<?php

namespace Yiisoft\Router;

use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Router\Middleware\Callback;

class Group implements RouteCollectorInterface
{
    private $items = [];
    private $prefix;
    private $middlewares = [];
    private $callback;
    private $callbackResolved = false;

    public function __construct(string $prefix = null, callable $callback = null)
    {
        $this->prefix = $prefix;
        $this->callback = $callback;
    }

    final public function addRoute(Route $route): void
    {
        $this->items[] = $route;
    }

    final public function addGroup(string $prefix, callable $callback): void
    {
        $this->items[] = new Group($prefix, $callback);
    }

    final public function addMiddleware($middleware): self
    {
        if (\is_callable($middleware)) {
            $middleware[] = new Callback($middleware);
        }

        if (!$middleware instanceof MiddlewareInterface) {
            throw new \InvalidArgumentException('Parameter should be either a PSR middleware or a callable.');
        }

        $this->middlewares[] = $middleware;

        return $this;
    }

    final public function getItems(): array
    {
        if (!$this->callbackResolved && $this->callback !== null) {
            $callback = $this->callback;
            $callback($this);
            $this->callbackResolved = true;
        }
        return $this->items;
    }

    final public function getPrefix(): string
    {
        return $this->prefix;
    }

    final public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
