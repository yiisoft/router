<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;

interface RouteParametersInterface
{
    public function getDispatcherWithMiddlewares(): MiddlewareDispatcher;

    public function getName(): string;

    public function getMethods(): array;

    public function getPattern(): string;

    public function getHost(): ?string;

    public function isOverride(): bool;

    public function getDefaults(): array;

    public function __toString(): string;
}
