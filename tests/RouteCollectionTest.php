<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\MiddlewareStack;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\Tests\Support\Container;

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

    public function testRouteWithoutAction(): void
    {
        $group = Group::create()
            ->middleware(fn() => 1)
            ->routes(
                Route::get('/test', $this->getDispatcher())->action(fn () => 2)->name('test'),
                Route::get('/images/{sile}')->name('image')
            );

        $routeCollection = new RouteCollection($group);
        $route = $routeCollection->getRoute('image');
        $this->assertFalse($route->hasMiddlewares());
    }

    private function getDispatcher(): MiddlewareDispatcher
    {
        return new MiddlewareDispatcher(
                new MiddlewareFactory($this->createMock(ContainerInterface::class)),
                new MiddlewareStack($this->createMock(EventDispatcherInterface::class))
            );
    }
}
