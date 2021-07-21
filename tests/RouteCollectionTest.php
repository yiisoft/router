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
use Yiisoft\Router\RouteCollector;

final class RouteCollectionTest extends TestCase
{
    public function testAddRouteWithDuplicateName(): void
    {
        $listRoute = Route::get('/')->name('my-route');
        $viewRoute = Route::get('/{id}')->name('my-route');

        $group = Group::create()->routes($listRoute, $viewRoute);

        $collector = new RouteCollector();
        $collector->addGroup($group);

        $this->expectExceptionMessage("A route with name 'my-route' already exists.");
        $routeCollection = new RouteCollection($collector);
        $routeCollection->getRoutes();
    }

    public function testRouteOverride(): void
    {
        $listRoute = Route::get('/')->name('my-route');
        $viewRoute = Route::get('/{id}')->name('my-route')->override();

        $group = Group::create()->routes($listRoute, $viewRoute);

        $collector = new RouteCollector();
        $collector->addGroup($group);

        $routeCollection = new RouteCollection($collector);
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

        $collector = new RouteCollector();
        $collector->addGroup($group);

        $routeCollection = new RouteCollection($collector);
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

        $collector = new RouteCollector();
        $collector->addGroup($group);

        $routeCollection = new RouteCollection($collector);
        $route1 = $routeCollection->getRoute('image');
        $route2 = $routeCollection->getRoute('project');
        $this->assertSame('https://yiiframework.com', $route1->getHost());
        $this->assertSame('https://yiipowered.com', $route2->getHost());
    }

    public function testGroupName(): void
    {
        $group = Group::create('api')
            ->routes(
                Group::create()->routes(
                    Group::create('/v1')->routes(
                        Route::get('/package/downloads/{package}')->name('/package/downloads')
                    )->namePrefix('/v1'),
                    Group::create()->routes(
                        Route::get('')->name('/index')
                    ),
                    Route::get('/post/{slug}')->name('/post/view'),
                    Route::get('/user/{username}'),
                )
            )->namePrefix('api');

        $collector = new RouteCollector();
        $collector->addGroup($group);

        $routeCollection = new RouteCollection($collector);
        $route1 = $routeCollection->getRoute('api/post/view');
        $route2 = $routeCollection->getRoute('api/v1/package/downloads');
        $route3 = $routeCollection->getRoute('api/index');
        $route4 = $routeCollection->getRoute('GET api/user/{username}');
        $this->assertInstanceOf(Route::class, $route1);
        $this->assertInstanceOf(Route::class, $route2);
        $this->assertInstanceOf(Route::class, $route3);
        $this->assertInstanceOf(Route::class, $route4);
    }

    private function getDispatcher(): MiddlewareDispatcher
    {
        return new MiddlewareDispatcher(
            new MiddlewareFactory($this->createMock(ContainerInterface::class)),
            $this->createMock(EventDispatcherInterface::class)
        );
    }
}
