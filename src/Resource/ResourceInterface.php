<?php

declare(strict_types=1);

namespace Yiisoft\Router\Resource;

use Yiisoft\Router\Group;
use Yiisoft\Router\Route;

/**
 * `ResourceInterface` is a resource of routes.
 */
interface ResourceInterface
{
    /**
     * @return Group[]|Route[]
     */
    public function getRoutes(): array;
}
