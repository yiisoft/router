<?php

declare(strict_types=1);

namespace Yiisoft\Router;

/**
 * An interface for denoting classes that represent a route.
 */
interface RoutableInterface
{
    public function toRoute(): Route|Group;
}
