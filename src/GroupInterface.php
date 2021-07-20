<?php

declare(strict_types=1);

namespace Yiisoft\Router;

interface GroupInterface
{
    public function middleware($middlewareDefinition): self;

    public function prependMiddleware($middlewareDefinition): self;

    public function disableMiddleware($middlewareDefinition): self;

    public function host(string $host): self;

    public function namePrefix(string $namePrefix): self;

    public function routes(...$routes): self;
}
