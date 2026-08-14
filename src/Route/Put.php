<?php

declare(strict_types=1);

namespace Yiisoft\Router\Route;

use Stringable;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

/**
 * Route that matches PUT requests.
 */
final class Put extends Route
{
    /**
     * Creates a route that matches PUT requests.
     *
     * @param string $pattern URL pattern to match.
     * @param string|null $name Route name.
     * @param array|callable|string|null $action Primary middleware definition that should be invoked last for a matched route.
     * @param array[]|callable[]|string[] $middlewares Handler middleware definitions that should be invoked for a matched route.
     * @param array<string,null|Stringable|scalar> $defaults Parameter default values indexed by parameter names.
     * @param string[] $hosts Hosts that the route applies to.
     * @param bool $override Whether the route should replace an existing route with the same name.
     * @param array[]|callable[]|string[] $disabledMiddlewares Middleware definitions to exclude when the action is handled.
     */
    public function __construct(
        string $pattern,
        ?string $name = null,
        array|callable|string|null $action = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        bool $override = false,
        array $disabledMiddlewares = [],
    ) {
        parent::__construct(
            $pattern,
            [Method::PUT],
            $name,
            $action,
            $middlewares,
            $defaults,
            $hosts,
            $override,
            $disabledMiddlewares,
        );
    }
}
