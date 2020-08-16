<?php

declare(strict_types=1);

namespace Yiisoft\Router;

use Yiisoft\Router\Interfaces\RouteInterface;

final class MatchingResult
{
    private RouteInterface $route;
    private bool $matchingSucceeded;

    private function __construct()
    {
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
        return $this->matchingSucceeded;
    }
}
