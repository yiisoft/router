<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Server\RequestHandlerInterface;

interface MiddlewareStackInterface extends RequestHandlerInterface
{
    public function build(array $middlewares, RequestHandlerInterface $fallbackHandler): MiddlewareStackInterface;

    public function reset(): void;

    public function isEmpty(): bool;
}
