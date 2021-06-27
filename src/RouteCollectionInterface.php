<?php

declare(strict_types=1);

namespace Yiisoft\Router;

interface RouteCollectionInterface
{
    /**
     * @return RouteParametersInterface[]
     */
    public function getRoutes(): array;

    /**
     * @param string $name
     *
     * @return RouteParametersInterface
     */
    public function getRoute(string $name): RouteParametersInterface;

    /**
     * Returns routes tree array
     *
     * @return array
     */
    public function getRouteTree(): array;
}
