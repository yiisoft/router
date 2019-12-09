<?php

namespace Yiisoft\Router;

use Psr\Http\Server\MiddlewareInterface;
use Yiisoft\Router\Middleware\Callback;

class Group implements RouteCollectorInterface
{
    protected $items = [];
    protected $prefix;
    protected $middlewares = [];

    public function __construct(string $prefix = null, callable $callback = null)
    {
        $this->prefix = $prefix;

        if ($callback !== null) {
            $callback($this);
        }
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
            $middleware = new Callback($middleware);
        }

        if (!$middleware instanceof MiddlewareInterface) {
            throw new \InvalidArgumentException('Parameter should be either a PSR middleware or a callable.');
        }

        $this->middlewares[] = $middleware;

        return $this;
    }

    final public function getItems(): array
    {
        return $this->items;
    }

    final public function getPrefix(): ?string
    {
        return $this->prefix;
    }

    final public function getMiddlewares(): array
    {
        return $this->middlewares;
    }
}
