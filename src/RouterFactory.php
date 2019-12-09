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
            if ($route instanceof Route) {
                $router->addRoute($route);
            } elseif (\is_array($route) && \count($route) === 2 && \is_string($route[0]) && \is_callable($route[1])) {
                $router->addGroup($route[0], $route[1]);
            } else {
                throw new \InvalidArgumentException('Routes should be eithe instances of Route or group arrays');
            }
        }
        return $router;
    }
}
