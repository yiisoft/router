<?php

namespace Yiisoft\Router;

use Psr\Http\Server\MiddlewareInterface;

class Group implements RouteCollectorInterface
{
    protected $items = [];
    protected $prefix;
    protected $middleware;
    protected $callback;

    public function addRoute(Route $route): void
    {
        $this->items[] = $route;
    }

    public function addGroup(string $prefix, callable $callback, MiddlewareInterface $middleware = null): void
    {
        $group = new Group();
        $group->prefix = $prefix;
        $group->callback = $callback;
        $group->middleware = $middleware;
        $this->items[] = $group;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getMiddleware(): MiddlewareInterface
    {
        return $this->middleware;
    }
}
