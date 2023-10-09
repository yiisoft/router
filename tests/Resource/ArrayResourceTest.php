<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Resource;

use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Group;
use Yiisoft\Router\Resource\ArrayResource;
use Yiisoft\Router\Route;

class ArrayResourceTest extends TestCase
{
    public function testGetRoutes(): void
    {
        $routes = [
            Route::get(''),
            Group::create('')->routes(Route::get('/blog')),
        ];

        $resource = new ArrayResource($routes);

        $this->assertSame($routes, $resource->getRoutes());
    }
}
