<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Psr\Http\Server\MiddlewareInterface;

interface MiddlewareFactoryInterface
{
    public function create($middlewareDefinition): MiddlewareInterface;
}
