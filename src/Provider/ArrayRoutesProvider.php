<?php

declare(strict_types=1);

namespace Yiisoft\Router\Provider;

use Yiisoft\Router\Route;
use Yiisoft\Router\Group;

final class ArrayRoutesProvider implements RoutesProviderInterface
{
    /**
     * @param Group[]|Route[] $routes
     */
    public function __construct(private readonly array $routes) {}

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
