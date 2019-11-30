<?php

namespace Yiisoft\Router;

use Psr\Http\Server\MiddlewareInterface;

class Group
{
    /**
     * @var Route[]
     */
    private $routes;

    /**
     * @var string
     */
    private $prefix;

    /**
     * @var GroupMiddlewareInterface
     */
    private $middleware;

    public function __construct(string $prefix, $middleware = null)
    {
        $this->prefix = $prefix;
        $this->middleware = $middleware;
    }

    public function addRoute(Route $route): self
    {
        $this->routes[] = $route;
        return $this;
    }

    /**
     * @return Route[]
     */
    public function getRoutes(): iterable
    {
        return $this->routes;
    }

    public function getPrefix(): string
    {
        return $this->prefix;
    }

    public function getMiddleware(): GroupMiddlewareInterface
    {
        return $this->middleware;
    }
}
