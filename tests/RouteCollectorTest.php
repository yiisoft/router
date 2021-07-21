<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\Tests\Support\Container;

final class RouteCollectorTest extends TestCase
{
    public function testAddRoute(): void
    {
        $listRoute = Route::get('/');
        $viewRoute = Route::get('/{id}');

        $collector = new RouteCollector();
        $collector->addRoute($listRoute);
        $collector->addRoute($viewRoute);

        $this->assertCount(2, $collector->getItems());
        $this->assertSame($listRoute, $collector->getItems()[0]);
        $this->assertSame($viewRoute, $collector->getItems()[1]);
    }

    public function testAddGroup(): void
    {
        $logoutRoute = Route::post('/logout');
        $listRoute = Route::get('/');
        $viewRoute = Route::get('/{id}');
        $postGroup = Group::create('/post')
            ->routes(
                $listRoute,
                $viewRoute
            );

        $rootGroup = Group::create()
            ->routes(
                Group::create('/api')
                    ->routes(
                        $logoutRoute,
                        $postGroup
                    ),
            );

        $collector = new RouteCollector();
        $collector->addGroup($rootGroup);
        $collector->addGroup($postGroup);

        $this->assertCount(2, $collector->getItems());
        $this->assertContainsOnlyInstancesOf(Group::class, $collector->getItems());
    }

    public function testAddMiddleware(): void
    {
        $collector = new RouteCollector();

        $middleware1 = static function () {
            return new Response();
        };
        $middleware2 = static function () {
            return new Response();
        };
        $middleware3 = static function () {
            return new Response();
        };

        $collector
            ->prependMiddleware($middleware3)
            ->middleware($middleware2)
            ->middleware($middleware1);
        $this->assertCount(3, $collector->getMiddlewareDefinitions());
        $this->assertSame($middleware1, $collector->getMiddlewareDefinitions()[0]);
        $this->assertSame($middleware2, $collector->getMiddlewareDefinitions()[1]);
        $this->assertSame($middleware3, $collector->getMiddlewareDefinitions()[2]);
    }
}
