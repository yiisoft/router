<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
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

    public function testRouteWithoutAction(): void
    {
        $group = Group::create()
            ->middleware(fn () => 1)
            ->routes(
                Route::get('/test', $this->getDispatcher())->action(fn () => 2)->name('test'),
                Route::get('/images/{sile}')->name('image')
            );

        $routeCollection = new RouteCollection($group);
        $route = $routeCollection->getRoute('image');
        $this->assertFalse($route->hasMiddlewares());
    }

    public function testGroupHost(): void
    {
        $group = Group::create()
            ->routes(
                Group::create()->routes(
                    Route::get('/project/{name}')->name('project')
                )->host('https://yiipowered.com/'),
                Route::get('/images/{name}')->name('image')
            )->host('https://yiiframework.com/');

        $routeCollection = new RouteCollection($group);
        $route1 = $routeCollection->getRoute('image');
        $route2 = $routeCollection->getRoute('project');
        $this->assertSame('https://yiiframework.com', $route1->getHost());
        $this->assertSame('https://yiipowered.com', $route2->getHost());
    }

    public function testGroupName(): void
    {
        $group = Group::create('api')
            ->routes(
                Group::create('/v1')->routes(
                    Route::get('/package/downloads/{package}')->name('/package/downloads')
                )->name('/v1'),
                Route::get('/post/{slug}')->name('/post/view'),
                Route::get('/user/{username}'),
            )->name('api');

        $routeCollection = new RouteCollection($group);
        $route1 = $routeCollection->getRoute('api/post/view');
        $route2 = $routeCollection->getRoute('api/v1/package/downloads');
        $route3 = $routeCollection->getRoute('GET api/user/{username}');
        $this->assertInstanceOf(Route::class, $route1);
        $this->assertInstanceOf(Route::class, $route2);
        $this->assertInstanceOf(Route::class, $route3);
    }

    private function getDispatcher(): MiddlewareDispatcher
    {
        return new MiddlewareDispatcher(
            new MiddlewareFactory($this->createMock(ContainerInterface::class)),
            $this->createMock(EventDispatcherInterface::class)
        );
    }
}
