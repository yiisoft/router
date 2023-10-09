<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Provider;

use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Group;
use Yiisoft\Router\Provider\ArrayRoutesProvider;
use Yiisoft\Router\Route;

class ArrayResourceTest extends TestCase
{
    public function testGetRoutes(): void
    {
        $routes = [
            Route::get(''),
            Group::create('')->routes(Route::get('/blog')),
        ];

        $resource = new ArrayRoutesProvider($routes);

        $this->assertSame($routes, $resource->getRoutes());
    }
}
