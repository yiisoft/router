<?php

declare(strict_types=1);

namespace Yiisoft\Router\Route;

use Stringable;
use Yiisoft\Router\Route;

abstract class MethodRoute extends Route
{
    /** @var string */
    protected const METHOD = '';

    /**
     * @param array|callable|string|null $action
     * @param array[]|callable[]|string[] $middlewares
     * @param array<string,null|Stringable|scalar> $defaults
     * @param string[] $hosts
     */
    final public function __construct(
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
            methods: [static::METHOD],
            pattern: $pattern,
            name: $name,
            action: $action,
            middlewares: $middlewares,
            defaults: $defaults,
            hosts: $hosts,
            override: $override,
            disabledMiddlewares: $disabledMiddlewares,
        );
    }
}
