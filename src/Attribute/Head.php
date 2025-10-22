<?php

declare(strict_types=1);

namespace Yiisoft\Router\Attribute;

use Attribute;
use Stringable;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS | Attribute::IS_REPEATABLE)]
final class Head implements RouteAttributeInterface
{
    private readonly Route $route;

    /**
     * @param array<string,scalar|Stringable|null> $defaults Parameter default values indexed by parameter names.
     * @param bool $override Marks route as override. When added it will replace existing route with the same name.
     * @param array $disabledMiddlewares Excludes middleware from being invoked when action is handled.
     * It is useful to avoid invoking one of the parent group middleware for
     * a certain route.
     */
    public function __construct(
        string $pattern,
        ?string $name = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        bool $override = false,
        array $disabledMiddlewares = []
    ) {
        $this->route = new Route(
            methods: [Method::HEAD],
            pattern: $pattern,
            name: $name,
            middlewares: $middlewares,
            defaults: $defaults,
            hosts: $hosts,
            override: $override,
            disabledMiddlewares: $disabledMiddlewares
        );
    }

    public function getRoute(): Route
    {
        return $this->route;
    }
}
