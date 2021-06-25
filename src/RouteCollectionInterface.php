<?php

declare(strict_types=1);

namespace Yiisoft\Router;

interface RouteCollectionInterface
{
    /**
     * @return RouteInterface[]
     */
    public function getRoutes(): array;

    /**
     * @param string $name
     *
     * @return RouteInterface
     */
    public function getRoute(string $name): RouteInterface;

    /**
     * Returns routes tree array
     *
     * @return array
     */
    public function getRouteTree(): array;
}
