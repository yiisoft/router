<?php

declare(strict_types=1);

namespace Yiisoft\Router;

interface RouteCollectionInterface
{
    /**
     * @return array
     */
    public function getItems(): array;

    /**
     * @return array
     */
    public function getRoutes(): array;

    /**
     * @param string $name
     * @return Route
     */
    public function getRoute(string $name): Route;
}
