<?php

declare(strict_types=1);

namespace Yiisoft\Router\Provider;

use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * Provides routes and route groups to route collector.
 */
interface RoutesProviderInterface
{
    /**
     * Returns an array of routes and/or route groups.
     *
     * @return Group[]|Route[]
     */
    public function getRoutes(): array;
}
