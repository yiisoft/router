<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollector;

final class RouteCollectorTest extends TestCase
{
    public function testAddRoute(): void
    {
        $listRoute = Route::get('/');
        $viewRoute = Route::get('/{id}');
        $topRoute = Route::get('/top');

        $collector = new RouteCollector();
        $collector->addRoute($listRoute, $viewRoute, top: $topRoute);

        $this->assertCount(3, $collector->getItems());
        $this->assertSame($listRoute, $collector->getItems()[0]);
        $this->assertSame($viewRoute, $collector->getItems()[1]);
        $this->assertSame($topRoute, $collector->getItems()[2]);
    }

    public function testAddGroup(): void
    {
        $logoutRoute = Route::post('/logout');
        $listRoute = Route::get('/');
        $viewRoute = Route::get('/{id}');
        $postGroup = Group::create('/post')
            ->routes(
                $listRoute,
                $viewRoute,
            );

        $rootGroup = Group::create()
            ->routes(
                Group::create('/api')
                    ->routes(
                        $logoutRoute,
                        $postGroup,
                    ),
            );

        $testGroup = Group::create()
            ->routes(
                Route::get('test/'),
            );

        $collector = new RouteCollector();
        $collector->addRoute($rootGroup, $postGroup, test: $testGroup);

        $this->assertCount(3, $collector->getItems());
        $this->assertContainsOnlyInstancesOf(Group::class, $collector->getItems());
    }

    public function testAddMiddleware(): void
    {
        $collector = new RouteCollector();

        $middleware1 = static fn() => new Response();
        $middleware2 = static fn() => new Response();
        $middleware3 = static fn() => new Response();
        $middleware4 = static fn() => new Response();
        $middleware5 = static fn() => new Response();

        $collector
            ->middleware($middleware3, $middleware4)
            ->middleware($middleware5)
            ->prependMiddleware($middleware1, $middleware2);
        $this->assertCount(5, $collector->getMiddlewareDefinitions());
        $this->assertSame($middleware1, $collector->getMiddlewareDefinitions()[0]);
        $this->assertSame($middleware2, $collector->getMiddlewareDefinitions()[1]);
        $this->assertSame($middleware3, $collector->getMiddlewareDefinitions()[2]);
        $this->assertSame($middleware4, $collector->getMiddlewareDefinitions()[3]);
        $this->assertSame($middleware5, $collector->getMiddlewareDefinitions()[4]);
    }

    public function testNamedArgumentsInMiddlewareMethods(): void
    {
        $collector = new RouteCollector();

        $middleware1 = static fn() => new Response();
        $middleware2 = static fn() => new Response();

        $collector
            ->middleware(a: $middleware2)
            ->prependMiddleware(b: $middleware1);
        $this->assertSame($middleware1, $collector->getMiddlewareDefinitions()[0]);
        $this->assertSame($middleware2, $collector->getMiddlewareDefinitions()[1]);
    }
}
