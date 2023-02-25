<?php

declare(strict_types=1);

namespace Yiisoft\Router;

interface RouteCollectionInterface
{
    /**
     * Returns URI prefix.
     * @see setUriPrefix()
     */
    public function getUriPrefix(): string;

    /**
     * Sets the URI prefix so that all routes are registered to this path after the domain part.
     */
    public function setUriPrefix(string $prefix): void;

    /**
     * @return Route[]
     */
    public function getRoutes(): array;

    public function getRoute(string $name): Route;

    /**
     * Returns routes tree array.
     */
    public function getRouteTree(): array;
}
