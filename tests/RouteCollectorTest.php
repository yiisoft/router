<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
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

        $collector = new RouteCollector($this->getDispatcher());
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

        $collector = new RouteCollector($this->getDispatcher());
        $collector->addGroup($rootGroup);
        $collector->addGroup($postGroup);

        $this->assertCount(2, $collector->getItems());
        $this->assertContainsOnlyInstancesOf(Group::class, $collector->getItems());
    }

    public function testAddMiddleware(): void
    {
        $collector = new RouteCollector($this->getDispatcher());

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

    public function testDispatcherInjected(): void
    {
        $dispatcher = $this->getDispatcher();

        $apiGroup = Group::create('/api')
            ->routes(
                Route::get('/info')->name('api-info'),
                Group::create('/v1')
                    ->routes(
                        Route::get('/user')->name('api-v1-user/index'),
                        Route::get('/user/{id}')->name('api-v1-user/view'),
                        Group::create('/news')
                            ->routes(
                                Route::get('/post')->name('api-v1-news-post/index'),
                                Route::get('/post/{id}')->name('api-v1-news-post/view'),
                            ),
                        Group::create('/blog')
                            ->routes(
                                Route::get('/post')->name('api-v1-blog-post/index'),
                                Route::get('/post/{id}')->name('api-v1-blog-post/view'),
                            ),
                        Route::get('/note')->name('api-v1-note/index'),
                        Route::get('/note/{id}')->name('api-v1-note/view'),
                    ),
                Group::create('/v2')
                    ->routes(
                        Route::get('/user')->name('api-v2-user/index'),
                        Route::get('/user/{id}')->name('api-v2-user/view'),
                        Group::create('/news')
                            ->routes(
                                Route::get('/post')->name('api-v2-news-post/index'),
                                Route::get('/post/{id}')->name('api-v2-news-post/view'),
                                Group::create('/blog')
                                    ->routes(
                                        Route::get('/post')->name('api-v2-blog-post/index'),
                                        Route::get('/post/{id}')->name('api-v2-blog-post/view'),
                                        Route::get('/note')->name('api-v2-note/index'),
                                        Route::get('/note/{id}')->name('api-v2-note/view')
                                    )
                            )
                    )
            );

        $collector = new RouteCollector($dispatcher);
        $collector->addGroup($apiGroup);
        $collection = new RouteCollection($collector);

        $items = $collection->getRoutes();

        $this->assertAllRoutesAndGroupsHaveDispatcher($items);
    }

    private function getDispatcher(): MiddlewareDispatcher
    {
        return new MiddlewareDispatcher(
            new MiddlewareFactory($this->getContainer()),
            $this->createMock(EventDispatcherInterface::class)
        );
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new Container($instances);
    }

    private function assertAllRoutesAndGroupsHaveDispatcher(array $items): void
    {
        $func = function ($item) use (&$func) {
            $this->assertTrue($item->hasDispatcher());
            if ($item instanceof Group) {
                $items = $item->getRoutes();
                array_walk($items, $func);
            }
        };
        array_walk($items, $func);
    }
}
