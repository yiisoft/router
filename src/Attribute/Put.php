<?php

namespace Yiisoft\Router\Attribute;

use Attribute;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Put extends Route
{
    public function __construct(
        string $pattern,
        ?string $name = null,
        array $middlewares = [],
        array $defaults = [],
        array $hosts = [],
        bool $override = false,
        array $disabledMiddlewares = []
    ) {
        parent::__construct(
            methods: [Method::PUT],
            pattern: $pattern,
            name: $name,
            middlewares: $middlewares,
            defaults: $defaults,
            hosts: $hosts,
            override: $override,
            disabledMiddlewares: $disabledMiddlewares
        );
    }
}
