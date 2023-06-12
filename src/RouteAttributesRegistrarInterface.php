<?php

declare(strict_types=1);

namespace Yiisoft\Router;

/**
 * `RouteAttributesRegistrarInterface` allows registering routes that declared in classes/functions via PHP Attributes.
 *
 * @see https://www.php.net/manual/en/language.attributes.php
 */
interface RouteAttributesRegistrarInterface
{
    /**
     * Registers routes to {@see RouteCollectorInterface}.
     */
    public function register(): void;
}
