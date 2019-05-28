<?php
namespace Yiisoft\Router;

class Group
{
    /**
     * @var Route[]
     */
    private $routes;
    private $prefix;

    public function __construct(string $prefix)
    {
        $this->prefix = $prefix;
    }

    public function addRoute(Route $route): self
    {
        // TODO: immutable?
        $this->routes[] = $route;
        return $this;
    }

    public function getRoutes(): iterable
    {
        foreach ($this->routes as $route) {
            yield $route->pattern($this->prefix . $route->getPattern());
        }
    }
}
