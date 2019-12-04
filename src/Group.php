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

    private function __construct()
    {
    }

    final public function addRoute(Route $route): void
    {
        $this->items[] = $route;
    }

    final public function addGroup(string $prefix, callable $callback): Group
    {
        $group = new Group();
        $group->prefix = $prefix;
        $group->callback = $callback;
        $this->items[] = $group;
        return $group;
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
