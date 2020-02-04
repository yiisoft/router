<?php

namespace Yiisoft\Router;

use InvalidArgumentException;

final class GroupFactory
{
    public function __invoke(?string $prefix = null, array $routes = []): Group
    {
        $group = new Group($prefix, function (RouteCollectorInterface $collector) use ($routes) {
            foreach ($routes as $route) {
                if ($route instanceof Route) {
                    $collector->addRoute($route);
                } elseif ($route instanceof Group) {
                    $collector->addGroupInstance($route);
                } elseif (is_array($route) && count($route) === 2 && is_string($route[0]) && is_callable($route[1])) {
                    $collector->addGroup($route[0], $route[1]);
                } else {
                    throw new InvalidArgumentException('Routes should be either instances of Route or Group or group arrays');
                }
            }
        });

        return $group;
    }
}
