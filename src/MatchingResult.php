<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Http\Method;

final class MatchingResult
{
    private bool $success;
    private ?Route $route = null;
    private array $parameters = [];
    private array $methods = [];

    private function __construct()
    {
    }

    public static function fromSuccess(Route $route, array $parameters): self
    {
        $new = new self();
        $new->success = true;
        $new->route = $route;
        $new->parameters = $parameters;
        return $new;
    }

    public static function fromFailure(array $methods): self
    {
        $new = new self();
        $new->methods = $methods;
        $new->success = false;
        return $new;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function isMethodFailure(): bool
    {
        return !$this->success && $this->methods !== Method::ALL;
    }

    public function parameters(): array
    {
        return $this->parameters;
    }

    public function methods(): array
    {
        return $this->methods;
    }

    public function getRouteHandlers(): array
    {
        if ($this->route === null) {
            return [];
        }

        $handlers = $this->route->getMiddlewareDefinitions();
        $handlers[] = $this->route->getHandler();
        return $handlers;
    }
}
