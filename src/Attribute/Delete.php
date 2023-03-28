<?php

declare(strict_types=1);

namespace Yiisoft\Router\Attribute;

use Attribute;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

#[Attribute(Attribute::TARGET_METHOD | Attribute::IS_REPEATABLE)]
final class Delete extends Route
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
            methods: [Method::DELETE],
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
