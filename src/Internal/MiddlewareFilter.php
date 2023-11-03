<?php

declare(strict_types=1);

namespace Yiisoft\Router\Internal;

/**
 * @internal
 */
final class MiddlewareFilter
{
    /**
     * @param array[]|callable[]|string[] $middlewares
     * @return array[]|callable[]|string[]
     *
     * @psalm-param list<array|callable|string> $middlewares
     * @psalm-return list<array|callable|string>
     */
    public static function filter(array $middlewares, array $disabledMiddlewares): array
    {
        $result = [];

        foreach ($middlewares as $middleware) {
            if (in_array($middleware, $disabledMiddlewares, true)) {
                continue;
            }

            $result[] = $middleware;
        }

        return $result;
    }
}
