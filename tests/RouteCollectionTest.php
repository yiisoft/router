<?php

namespace Yiisoft\Router\Tests;

use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;

class RouteCollectionTest extends TestCase
{
    public function testAddRouteWithDuplicateName(): void
    {
        $listRoute = Route::get('/')->name('my-route');
        $viewRoute = Route::get('/{id}')->name('my-route');

        $group = Group::create();
        $group->addRoute($listRoute);
        $group->addRoute($viewRoute);
        $this->expectExceptionMessage("A route with name 'my-route' already exists.");
        new RouteCollection($group);
    }
}
