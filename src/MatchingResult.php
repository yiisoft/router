<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Router\Route\RouteInterface;

final class MatchingResult
{
    private RouteInterface $route;
    private array $parameters;
    private bool $success;

    private function __construct()
    {
    }

    public static function fromSuccess(RouteInterface $route, array $parameters): self
    {
        $result = new self();
        $result->success = true;
        $result->route = $route;
        $result->parameters = $parameters;

        return $result;
    }

    public static function fromFailure(): self
    {
        $result = new self();
        $result->success = false;

        return $result;
    }

    /**
     * Get the route for which matching was done.
     *
     * @return RouteInterface
     */
    public function getRoute(): RouteInterface
    {
        return $this->route;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }
}
