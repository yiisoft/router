<?php

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\ServerRequest;
use Nyholm\Psr7\Response;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\Middleware\Callback;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\Tests\Support\Container;

class GroupTest extends TestCase
{
    public function testAddRoute(): void
    {
        $listRoute = Route::get('/');
        $viewRoute = Route::get('/{id}');

        $group = Group::create();
        $group->addRoute($listRoute);
        $group->addRoute($viewRoute);

        $this->assertCount(2, $group->getItems());
        $this->assertSame($listRoute, $group->getItems()[0]);
        $this->assertSame($viewRoute, $group->getItems()[1]);
    }

    public function testAddMiddleware(): void
    {
        $group = Group::create();

        $middleware1 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $middleware2 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();

        $group
            ->addMiddleware($middleware1)
            ->addMiddleware($middleware2);

        $this->assertCount(2, $group->getMiddlewares());
        $this->assertSame($middleware1, $group->getMiddlewares()[0]);
        $this->assertSame($middleware2, $group->getMiddlewares()[1]);
    }

    public function testAddNestedMiddleware(): void
    {
        $request = new ServerRequest('GET', '/outergroup/innergroup/test1');

        $middleware1 = new Callback(static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware1');
            return $handler->handle($request);
        }, $this->getContainer());

        $middleware2 = new Callback(static function (ServerRequestInterface $request) {
            return new Response(200, [], null, '1.1', implode($request->getAttributes()));
        }, $this->getContainer());

        $group = Group::create('/outergroup', [
            Group::create('/innergroup', [
                Route::get('/test1')->name('request1')
            ])->addMiddleware($middleware2),
        ])->addMiddleware($middleware1);

        $collector = Group::create();
        $collector->addGroup($group);

        $routeCollection = new RouteCollection($collector);
        $route = $routeCollection->getRoute('request1');
        $response = $route->process($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware1', $response->getReasonPhrase());
    }

    public function testGroupMiddlewareFullStackCalled(): void
    {
        $group = Group::create('/group', function (RouteCollectorInterface $r) {
            $r->addRoute(Route::get('/test1')->name('request1'));
        });

        $request = new ServerRequest('GET', '/group/test1');
        $middleware1 = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware1');
            return $handler->handle($request);
        }, $this->getContainer());
        $middleware2 = new Callback(function (ServerRequestInterface $request) {
            return new Response(200, [], null, '1.1', implode($request->getAttributes()));
        }, $this->getContainer());

        $group->addMiddleware($middleware2)->addMiddleware($middleware1);
        $collector = Group::create();
        $collector->addGroup($group);

        $routeCollection = new RouteCollection($collector);
        $route = $routeCollection->getRoute('request1');
        $response = $route->process($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware1', $response->getReasonPhrase());
    }

    public function testGroupMiddlewareStackInterrupted(): void
    {
        $group = Group::create('/group', function (RouteCollectorInterface $r) {
            $r->addRoute(Route::get('/test1')->name('request1'));
        });

        $request = new ServerRequest('GET', '/group/test1');
        $middleware1 = new Callback(function () {
            return new Response(403);
        }, $this->getContainer());
        $middleware2 = new Callback(function () {
            return new Response(200);
        }, $this->getContainer());

        $group->addMiddleware($middleware2)->addMiddleware($middleware1);
        $collector = Group::create();
        $collector->addGroup($group);

        $routeCollection = new RouteCollection($collector);
        $route = $routeCollection->getRoute('request1');
        $response = $route->process($request, $this->getRequestHandler());
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAddGroup(): void
    {
        $logoutRoute = Route::post('/logout');
        $listRoute = Route::get('/');
        $viewRoute = Route::get('/{id}');

        $middleware1 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $middleware2 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();

        $root = Group::create();
        $root->addGroup(Group::create('/api', static function (Group $group) use ($logoutRoute, $listRoute, $viewRoute, $middleware1, $middleware2) {
            $group->addRoute($logoutRoute);
            $group->addGroup(Group::create('/post', static function (Group $group) use ($listRoute, $viewRoute) {
                $group->addRoute($listRoute);
                $group->addRoute($viewRoute);
            }));

            $group->addMiddleware($middleware1);
            $group->addMiddleware($middleware2);
        }));

        $this->assertCount(1, $root->getItems());
        $api = $root->getItems()[0];

        $this->assertSame('/api', $api->getPrefix());
        $this->assertCount(2, $api->getItems());
        $this->assertSame($logoutRoute, $api->getItems()[0]);

        /** @var Group $postGroup */
        $postGroup = $api->getItems()[1];
        $this->assertInstanceOf(Group::class, $postGroup);
        $this->assertCount(2, $api->getMiddlewares());
        $this->assertSame($middleware1, $api->getMiddlewares()[0]);
        $this->assertSame($middleware2, $api->getMiddlewares()[1]);

        $this->assertSame('/post', $postGroup->getPrefix());
        $this->assertCount(2, $postGroup->getItems());
        $this->assertSame($listRoute, $postGroup->getItems()[0]);
        $this->assertSame($viewRoute, $postGroup->getItems()[1]);
        $this->assertEmpty($postGroup->getMiddlewares());
    }

    public function testAddGroupSecondWay(): void
    {
        $logoutRoute = Route::post('/logout');
        $listRoute = Route::get('/');
        $viewRoute = Route::get('/{id}');

        $middleware1 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $middleware2 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();

        $root = Group::create(null, [
            Group::create('/api', [
                $logoutRoute,
                Group::create('/post', [
                    $listRoute,
                    $viewRoute
                ])
            ])->addMiddleware($middleware1)->addMiddleware($middleware2)
        ]);

        $this->assertCount(1, $root->getItems());
        $api = $root->getItems()[0];

        $this->assertSame('/api', $api->getPrefix());
        $this->assertCount(2, $api->getItems());
        $this->assertSame($logoutRoute, $api->getItems()[0]);

        /** @var Group $postGroup */
        $postGroup = $api->getItems()[1];
        $this->assertInstanceOf(Group::class, $postGroup);
        $this->assertCount(2, $api->getMiddlewares());
        $this->assertSame($middleware1, $api->getMiddlewares()[0]);
        $this->assertSame($middleware2, $api->getMiddlewares()[1]);

        $this->assertSame('/post', $postGroup->getPrefix());
        $this->assertCount(2, $postGroup->getItems());
        $this->assertSame($listRoute, $postGroup->getItems()[0]);
        $this->assertSame($viewRoute, $postGroup->getItems()[1]);
        $this->assertEmpty($postGroup->getMiddlewares());
    }

    public function testContainerInjected(): void
    {
        $container = $this->getContainer();

        $apiGroup = Group::create(
            '/api',
            static function (Group $group) {
                $group->addRoute(Route::get('/info')->name('api-info'));
                $group->addGroup(
                    Group::create(
                        '/v1',
                        static function (Group $group) {
                            $group->addRoute(Route::get('/user')->name('api-v1-user/index'));
                            $group->addRoute(Route::get('/user/{id}')->name('api-v1-user/view'));
                            $group->addGroup(
                                Group::create(
                                    '/news',
                                    static function (Group $group) {
                                        $group->addRoute(Route::get('/post')->name('api-v1-news-post/index'));
                                        $group->addRoute(Route::get('/post/{id}')->name('api-v1-news-post/view'));
                                    }
                                )
                            );
                            $group->addGroup(
                                Group::create(
                                    '/blog',
                                    static function (Group $group) {
                                        $group->addRoute(Route::get('/post')->name('api-v1-blog-post/index'));
                                        $group->addRoute(Route::get('/post/{id}')->name('api-v1-blog-post/view'));
                                    }
                                )
                            );
                            $group->addRoute(Route::get('/note')->name('api-v1-note/index'));
                            $group->addRoute(Route::get('/note/{id}')->name('api-v1-note/view'));
                        }
                    )
                );
                $group->addGroup(
                    Group::create(
                        '/v2',
                        static function (Group $group) {
                            $group->addRoute(Route::get('/user')->name('api-v2-user/index'));
                            $group->addRoute(Route::get('/user/{id}')->name('api-v2-user/view'));
                            $group->addGroup(
                                Group::create(
                                    '/news',
                                    static function (Group $group) {
                                        $group->addRoute(Route::get('/post')->name('api-v2-news-post/index'));
                                        $group->addRoute(Route::get('/post/{id}')->name('api-v2-news-post/view'));
                                    }
                                )
                            );
                            $group->addGroup(
                                Group::create(
                                    '/blog',
                                    static function (Group $group) {
                                        $group->addRoute(Route::get('/post')->name('api-v2-blog-post/index'));
                                        $group->addRoute(Route::get('/post/{id}')->name('api-v2-blog-post/view'));
                                    }
                                )
                            );
                            $group->addRoute(Route::get('/note')->name('api-v2-note/index'));
                            $group->addRoute(Route::get('/note/{id}')->name('api-v2-note/view'));
                        }
                    )
                );
            },
            $container
        );

        $items = $apiGroup->getItems();

        $this->assertAllRoutesAndGroupsHaveContainer($items);
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

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new Container($instances);
    }

    private function assertAllRoutesAndGroupsHaveContainer(array $items): void
    {
        $func = function ($item) use (&$func) {
            $this->assertTrue($item->hasContainer());
            if ($item instanceof Group) {
                $items = $item->getItems();
                array_walk($items, $func);
            }
        };
        array_walk($items, $func);
    }
}
