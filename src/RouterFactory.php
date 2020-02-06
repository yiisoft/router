<?php

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;

final class RouterFactory
{
    /**
     * @var callable
     */
    private $engineFactory;
    /**
     * @var Route[]
     */
    private array $routes;

    public function __construct(callable $engineFactory, array $routes = [])
    {
        $this->engineFactory = $engineFactory;
        $this->routes = $routes;
    }

    public function __invoke(ContainerInterface $container): RouterInterface
    {
        $factory = $this->engineFactory;
        /* @var $router RouterInterface */
        $router = $factory();
        if (!$router->hasContainer()) {
            $router = $router->withContainer($container);
        }
        foreach ($this->routes as $route) {
            if ($route instanceof Route) {
                $router->addRoute($route);
            } elseif ($route instanceof Group) {
                $router->addGroup($route);
            } else {
                throw new InvalidArgumentException('Routes should be either instances of Route or Group');
            }
        }
        return $router;
    }
}
