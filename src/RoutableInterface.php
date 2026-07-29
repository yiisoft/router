<?php

declare(strict_types=1);

namespace Yiisoft\Router;

/**
 * Represents a custom route or route-group definition.
 *
 * The route collection clones the object returned by {@see toRoute()} before applying collection or group
 * configuration, so implementations may safely return a retained object.
 */
interface RoutableInterface
{
    public function toRoute(): Route|Group;
}
