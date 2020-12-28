<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;

final class RouteCollectionTest extends TestCase
{
    public function testRouteOverride(): void
    {
        $listRoute = Route::get('/')->name('my-route');
        $viewRoute = Route::get('/{id}')->name('my-route');

        $group = Group::create();
        $group->addRoute($listRoute);
        $group->addRoute($viewRoute);

        $routeCollection = new RouteCollection($group);
        $route = $routeCollection->getRoute('my-route');
        $this->assertSame('/{id}', $route->getPattern());
    }
}
