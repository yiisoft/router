<?php

declare(strict_types=1);

namespace Yiisoft\Router\Resource;

use Yiisoft\Router\Route;
use Yiisoft\Router\Group;

final class ArrayResource implements ResourceInterface
{
    /**
     * @param Route[]|Group[] $routes
     */
    public function __construct(private array $routes)
    {
    }

    public function getRoutes(): array
    {
        return $this->routes;
    }
}
