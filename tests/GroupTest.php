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

final class GroupTest extends TestCase
{
    public function testAddMiddleware(): void
    {
        $group = Group::create();

        $middleware1 = static function () {
            return new Response();
        };
        $middleware2 = static function () {
            return new Response();
        };

        $group = $group
            ->middleware($middleware2)
            ->middleware($middleware1);
        $this->assertCount(2, $group->getMiddlewareDefinitions());
        $this->assertSame($middleware1, $group->getMiddlewareDefinitions()[0]);
        $this->assertSame($middleware2, $group->getMiddlewareDefinitions()[1]);
    }

    public function testAddNestedMiddleware(): void
    {
        $request = new ServerRequest('GET', '/outergroup/innergroup/test1');

        $action = static function (ServerRequestInterface $request) {
            return new Response(200, [], null, '1.1', implode($request->getAttributes()));
        };

        $middleware1 = static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware1');
            return $handler->handle($request);
        };

        $middleware2 = static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware2');
            return $handler->handle($request);
        };

        $group = Group::create('/outergroup', $this->getDispatcher())
            ->middleware($middleware1)
            ->routes(
                Group::create('/innergroup')
                    ->middleware($middleware2)
                    ->routes(
                        Route::get('/test1')->action($action)->name('request1'),
                    )
            );

        $collector = new RouteCollector($this->getDispatcher());
        $collector->addGroup($group);

        $routeCollection = new RouteCollection($collector);
        $route = $routeCollection->getRoute('request1');
        $response = $route->getDispatcherWithMiddlewares()->dispatch($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware2', $response->getReasonPhrase());
    }

    public function testGroupMiddlewareFullStackCalled(): void
    {
        $request = new ServerRequest('GET', '/group/test1');

        $action = static function (ServerRequestInterface $request) {
            return new Response(200, [], null, '1.1', implode($request->getAttributes()));
        };
        $middleware1 = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware1');
            return $handler->handle($request);
        };
        $middleware2 = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware2');
            return $handler->handle($request);
        };

        $group = Group::create('/group', $this->getDispatcher())
            ->middleware($middleware1)
            ->middleware($middleware2)
            ->routes(
                Route::get('/test1')->action($action)->name('request1'),
            );

        $collector = new RouteCollector($this->getDispatcher());
        $collector->addGroup($group);

        $routeCollection = new RouteCollection($collector);
        $route = $routeCollection->getRoute('request1');
        $response = $route->getDispatcherWithMiddlewares()->dispatch($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware2', $response->getReasonPhrase());
    }

    public function testGroupMiddlewareStackInterrupted(): void
    {
        $request = new ServerRequest('GET', '/group/test1');

        $action = static function (ServerRequestInterface $request) {
            return new Response(200);
        };
        $middleware1 = function () {
            return new Response(403);
        };
        $middleware2 = function () {
            return new Response(405);
        };

        $group = Group::create('/group', $this->getDispatcher())
            ->middleware($middleware1)
            ->middleware($middleware2)
            ->routes(
                Route::get('/test1')->action($action)->name('request1')
            );

        $collector = new RouteCollector($this->getDispatcher());
        $collector->addGroup($group);

        $routeCollection = new RouteCollection($collector);
        $route = $routeCollection->getRoute('request1');
        $response = $route->getDispatcherWithMiddlewares()->dispatch($request, $this->getRequestHandler());
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAddGroup(): void
    {
        $logoutRoute = Route::post('/logout');
        $listRoute = Route::get('/');
        $viewRoute = Route::get('/{id}');

        $middleware1 = static function () {
            return new Response();
        };
        $middleware2 = static function () {
            return new Response();
        };

        $root = Group::create(null)
            ->routes(
                Group::create('/api')
                    ->middleware($middleware2)
                    ->middleware($middleware1)
                    ->routes(
                        $logoutRoute,
                        Group::create('/post')
                            ->routes(
                                $listRoute,
                                $viewRoute
                            )
                    ),
            );

        $this->assertCount(1, $root->getRoutes());
        $api = $root->getRoutes()[0];

        $this->assertSame('/api', $api->getPrefix());
        $this->assertCount(2, $api->getRoutes());
        $this->assertSame($logoutRoute, $api->getRoutes()[0]);

        /** @var Group $postGroup */
        $postGroup = $api->getRoutes()[1];
        $this->assertInstanceOf(Group::class, $postGroup);
        $this->assertCount(2, $api->getMiddlewareDefinitions());
        $this->assertSame($middleware1, $api->getMiddlewareDefinitions()[0]);
        $this->assertSame($middleware2, $api->getMiddlewareDefinitions()[1]);

        $this->assertSame('/post', $postGroup->getPrefix());
        $this->assertCount(2, $postGroup->getRoutes());
        $this->assertSame($listRoute, $postGroup->getRoutes()[0]);
        $this->assertSame($viewRoute, $postGroup->getRoutes()[1]);
        $this->assertEmpty($postGroup->getMiddlewareDefinitions());
    }

    public function testHost()
    {
        $group = Group::create()->host('https://yiiframework.com/');

        $this->assertSame($group->getHost(), 'https://yiiframework.com');
    }

    public function testName()
    {
        $group = Group::create()->namePrefix('api');

        $this->assertSame($group->getNamePrefix(), 'api');
    }

    public function testDispatcherInjected(): void
    {
        $dispatcher = $this->getDispatcher();

        $apiGroup = Group::create('/api', $dispatcher)
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

        $items = $apiGroup->getRoutes();

        $this->assertAllRoutesAndGroupsHaveDispatcher($items);
    }

    private function getRequestHandler(): RequestHandlerInterface
    {
        return new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(404);
            }
        };
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
