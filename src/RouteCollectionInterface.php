<?php

declare(strict_types=1);

namespace Yiisoft\Router;

interface RouteCollectionInterface
{
    /**
     * @return Route[]
     */
    public function getRoutes(): array;

    /**
     * @param string $name
     *
     * @return Route
     */
    public function getRoute(string $name): Route;

    /**
     * Returns routes tree array.
     *
     * @return array
     */
    public function getRouteTree(): array;
}
