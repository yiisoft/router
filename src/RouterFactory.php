<?php
namespace Yiisoft\Router;

class RouterFactory
{
    private $engineFactory;
    private $routes;

    public function __construct(callable $engineFactory, $routes = [])
    {
        $this->engineFactory = $engineFactory;
        $this->routes = $routes;
    }

    public function __invoke(): RouterInterface
    {
        $factory = $this->engineFactory;
        /* @var $router RouterInterface */
        $router = $factory();
        foreach ($this->routes as $route) {
            $router->addRoute($route);
        }
        return $router;
    }

    public static function __set_state(array $properties): self
    {
        return new self($properties['engineFactory'], $properties['routes']);
    }
}
