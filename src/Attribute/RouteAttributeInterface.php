<?php

declare(strict_types=1);

namespace Yiisoft\Router\Attribute;

use Yiisoft\Router\Route;

/**
 * Interface for route attributes that can provide a route instance.
 */
interface RouteAttributeInterface
{
    /**
     * Returns the route instance defined by this attribute.
     *
     * @return Route The route instance.
     */
    public function getRoute(): Route;
}
