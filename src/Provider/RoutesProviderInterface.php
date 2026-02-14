<?php

declare(strict_types=1);

namespace Yiisoft\Router\Provider;

use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * `RoutesProviderInterface` provides routes.
 */
interface RoutesProviderInterface
{
    /**
     * @return Group[]|Route[]
     */
    public function getRoutes(): array;
}
