<?php

namespace Yiisoft\Router;

use Psr\Container\ContainerInterface;

final class RouterFactory
{
    private $engineFactory;
    private $routes;

    public function __construct(callable $engineFactory, $routes = [])
    {
        $this->engineFactory = $engineFactory;
        $this->routes = $routes;
    }

    public function __invoke(ContainerInterface $container): RouterInterface
    {
        $factory = $this->engineFactory;
        /* @var $router RouterInterface */
        $router = $factory($container);
        foreach ($this->routes as $route) {
            $router->addRoute($route);
        }
        return $router;
    }
}
