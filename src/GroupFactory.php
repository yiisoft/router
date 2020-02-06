<?php

namespace Yiisoft\Router;

use InvalidArgumentException;
use Psr\Container\ContainerInterface;

final class GroupFactory
{
    private ?ContainerInterface $container = null;

    public function __construct(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    public function __invoke(?string $prefix = null, array $routes = []): Group
    {
        $group = new Group($prefix, function (Group $group) use ($routes) {
            foreach ($routes as $route) {
                if ($route instanceof Route) {
                    $group->addRoute($route);
                } elseif ($route instanceof Group) {
                    $group->addGroup($route);
                } else {
                    throw new InvalidArgumentException('Routes should be either instances of Route or Group');
                }
            }
        }, $this->container);

        return $group;
    }
}
