<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;

final class RouteCollectionTest extends TestCase
{
    public function testAddRouteWithDuplicateName(): void
    {
        $listRoute = Route::get('/')->name('my-route');
        $viewRoute = Route::get('/{id}')->name('my-route');

        $group = Group::create();
        $group->addRoute($listRoute);
        $group->addRoute($viewRoute);
        $this->expectExceptionMessage("A route with name 'my-route' already exists.");
        $routeCollection = new RouteCollection($group);
        $routeCollection->getRoutes();
    }

    public function testRouteOverride(): void
    {
        $listRoute = Route::get('/')->name('my-route');
        $viewRoute = Route::get('/{id}')->name('my-route')->override();

        $group = Group::create();
        $group->addRoute($listRoute);
        $group->addRoute($viewRoute);

        $routeCollection = new RouteCollection($group);
        $route = $routeCollection->getRoute('my-route');
        $this->assertSame('/{id}', $route->getPattern());
    }
}
