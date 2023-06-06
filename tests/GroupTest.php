<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use InvalidArgumentException;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\Tests\Support\Container;
use Yiisoft\Router\Tests\Support\TestMiddleware1;
use Yiisoft\Router\Tests\Support\TestMiddleware2;
use Yiisoft\Router\Tests\Support\TestMiddleware3;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class GroupTest extends TestCase
{
    public function testAddMiddleware(): void
    {
        $group = Group::create();

        $middleware1 = static fn () => new Response();
        $middleware2 = static fn () => new Response();

        $group = $group
            ->middleware($middleware1)
            ->middleware($middleware2);
        $this->assertCount(2, $group->getData('middlewareDefinitions'));
        $this->assertSame($middleware1, $group->getData('middlewareDefinitions')[0]);
        $this->assertSame($middleware2, $group->getData('middlewareDefinitions')[1]);
    }

    public function testDisabledMiddlewareDefinitions(): void
    {
        $group = Group::create()
            ->middleware(TestMiddleware3::class)
            ->prependMiddleware(TestMiddleware1::class, TestMiddleware2::class)
            ->disableMiddleware(TestMiddleware1::class, TestMiddleware3::class);

        $this->assertCount(1, $group->getData('middlewareDefinitions'));
        $this->assertSame(TestMiddleware2::class, $group->getData('middlewareDefinitions')[0]);
    }

    public function testNamedArgumentsInMiddlewareMethods(): void
    {
        $group = Group::create()
            ->middleware(middleware: TestMiddleware3::class)
            ->prependMiddleware(middleware1: TestMiddleware1::class, middleware2: TestMiddleware2::class)
            ->disableMiddleware(middleware1: TestMiddleware1::class, middleware2: TestMiddleware3::class);

        $this->assertCount(1, $group->getData('middlewareDefinitions'));
        $this->assertSame(TestMiddleware2::class, $group->getData('middlewareDefinitions')[0]);
    }

    public function testRoutesAfterMiddleware(): void
    {
        $group = Group::create();

        $middleware1 = static fn () => new Response();

        $group = $group->prependMiddleware($middleware1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('routes() can not be used after prependMiddleware().');

        $group->routes(Route::get('/'));
    }

    public function testAddNestedMiddleware(): void
    {
        $request = new ServerRequest('GET', '/outergroup/innergroup/test1');

        $action = static fn (ServerRequestInterface $request) => new Response(200, [], null, '1.1', implode($request->getAttributes()));

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
                        Route::get('/test1')
                            ->action($action)
                            ->name('request1'),
                    )
            );

        $collector = new RouteCollector();
        $collector->addRoute($group);

        $routeCollection = new RouteCollection($collector);
        $route = $routeCollection->getRoute('request1');
        $response = $route
            ->getData('dispatcherWithMiddlewares')
            ->dispatch($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware2', $response->getReasonPhrase());
    }

    public function testGroupMiddlewareFullStackCalled(): void
    {
        $request = new ServerRequest('GET', '/group/test1');

        $action = static fn (ServerRequestInterface $request) => new Response(200, [], null, '1.1', implode($request->getAttributes()));
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
                Route::get('/test1')
                    ->action($action)
                    ->name('request1'),
            );

        $collector = new RouteCollector();
        $collector->addRoute($group);

        $routeCollection = new RouteCollection($collector);
        $route = $routeCollection->getRoute('request1');
        $response = $route
            ->getData('dispatcherWithMiddlewares')
            ->dispatch($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware2', $response->getReasonPhrase());
    }

    public function testGroupMiddlewareStackInterrupted(): void
    {
        $request = new ServerRequest('GET', '/group/test1');

        $action = static fn () => new Response(200);
        $middleware1 = fn () => new Response(403);
        $middleware2 = fn () => new Response(405);

        $group = Group::create('/group', $this->getDispatcher())
            ->middleware($middleware1)
            ->middleware($middleware2)
            ->routes(
                Route::get('/test1')
                    ->action($action)
                    ->name('request1')
            );

        $collector = new RouteCollector();
        $collector->addRoute($group);

        $routeCollection = new RouteCollection($collector);
        $route = $routeCollection->getRoute('request1');
        $response = $route
            ->getData('dispatcherWithMiddlewares')
            ->dispatch($request, $this->getRequestHandler());
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAddGroup(): void
    {
        $logoutRoute = Route::post('/logout');
        $listRoute = Route::get('/');
        $viewRoute = Route::get('/{id}');

        $middleware1 = static fn () => new Response();
        $middleware2 = static fn () => new Response();

        $root = Group::create()
            ->routes(
                Group::create('/api')
                    ->middleware($middleware1)
                    ->middleware($middleware2)
                    ->routes(
                        $logoutRoute,
                        Group::create('/post')
                            ->routes(
                                $listRoute,
                                $viewRoute
                            )
                    ),
            );

        $this->assertCount(1, $root->getData('items'));

        /** @var Group $api */
        $api = $root->getData('items')[0];

        $this->assertSame('/api', $api->getData('prefix'));
        $this->assertCount(2, $api->getData('items'));
        $this->assertSame($logoutRoute, $api->getData('items')[0]);

        /** @var Group $postGroup */
        $postGroup = $api->getData('items')[1];
        $this->assertInstanceOf(Group::class, $postGroup);
        $this->assertCount(2, $api->getData('middlewareDefinitions'));
        $this->assertSame($middleware1, $api->getData('middlewareDefinitions')[0]);
        $this->assertSame($middleware2, $api->getData('middlewareDefinitions')[1]);

        $this->assertSame('/post', $postGroup->getData('prefix'));
        $this->assertCount(2, $postGroup->getData('items'));
        $this->assertSame($listRoute, $postGroup->getData('items')[0]);
        $this->assertSame($viewRoute, $postGroup->getData('items')[1]);
        $this->assertEmpty($postGroup->getData('middlewareDefinitions'));
    }

    public function testHost(): void
    {
        $group = Group::create()->host('https://yiiframework.com/');

        $this->assertSame('https://yiiframework.com', $group->getData('host'));
    }

    public function testHosts(): void
    {
        $group = Group::create()->hosts('https://yiiframework.com/', 'https://yiiframework.ru/');

        $this->assertSame(['https://yiiframework.com', 'https://yiiframework.ru'], $group->getData('hosts'));
    }

    public function testName(): void
    {
        $group = Group::create()->namePrefix('api');

        $this->assertSame('api', $group->getData('namePrefix'));
    }

    public function testGetDataWithWrongKey(): void
    {
        $group = Group::create();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown data key: wrong');

        $group->getData('wrong');
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

        $items = $apiGroup->getData('items');

        $this->assertAllRoutesAndGroupsHaveDispatcher($items);
    }

    public function testWithCors(): void
    {
        $group = Group::create()
            ->routes(
                Route::get('/info')->action(static fn () => 'info'),
                Route::post('/info')->action(static fn () => 'info'),
            )
            ->withCors(
                static fn () => new Response(204)
            );

        $collector = new RouteCollector();
        $collector->addRoute($group);
        $routeCollection = new RouteCollection($collector);

        $this->assertCount(3, $routeCollection->getRoutes());
    }

    public function testWithCorsDoesntDuplicateRoutes(): void
    {
        $group = Group::create()
            ->routes(
                Route::get('/info')
                    ->action(static fn () => 'info')
                    ->host('yii.dev'),
                Route::post('/info')
                    ->action(static fn () => 'info')
                    ->host('yii.dev'),
                Route::put('/info')
                    ->action(static fn () => 'info')
                    ->host('yii.test'),
            )
            ->withCors(
                static fn () => new Response(204)
            );

        $collector = new RouteCollector();
        $collector->addRoute($group);
        $routeCollection = new RouteCollection($collector);

        $this->assertCount(5, $routeCollection->getRoutes());
    }

    public function testWithCorsWithNestedGroups(): void
    {
        $group = Group::create()->routes(
            Route::get('/info')->action(static fn () => 'info'),
            Route::post('/info')->action(static fn () => 'info'),
            Group::create('/v1')
                ->routes(
                    Route::get('/post')->action(static fn () => 'post'),
                    Route::post('/post')->action(static fn () => 'post'),
                    Route::options('/options')->action(static fn () => 'options'),
                )
                ->withCors(
                    static fn () => new Response(201)
                )
        )->withCors(
            static fn () => new Response(204)
        );

        $collector = new RouteCollector();
        $collector->addRoute($group);

        $routeCollection = new RouteCollection($collector);
        $this->assertCount(7, $routeCollection->getRoutes());
        $this->assertInstanceOf(Route::class, $routeCollection->getRoute('OPTIONS /v1/post'));
    }

    public function testWithCorsWithNestedGroups2(): void
    {
        $group = Group::create()->routes(
            Route::get('/info')->action(static fn () => 'info'),
            Route::post('/info')->action(static fn () => 'info'),
            Route::get('/v1/post')->action(static fn () => 'post'),
            Group::create('/v1')->routes(
                Route::post('/post')->action(static fn () => 'post'),
                Route::options('/options')->action(static fn () => 'options'),
            ),
            Group::create('/v1')->routes(
                Route::put('/post')->action(static fn () => 'post'),
            )
        )->withCors(
            static fn () => new Response(204)
        );
        $collector = new RouteCollector();
        $collector->addRoute($group);

        $routeCollection = new RouteCollection($collector);
        $this->assertCount(8, $routeCollection->getRoutes());
        $this->assertInstanceOf(Route::class, $routeCollection->getRoute('OPTIONS /v1/post'));
    }

    public function testMiddlewareAfterRoutes(): void
    {
        $group = Group::create()->routes(Route::get('/info')->action(static fn () => 'info'));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('middleware() can not be used after routes().');
        $group->middleware(static fn () => new Response());
    }

    public function testDuplicateHosts(): void
    {
        $route = Group::create()->hosts('a.com', 'b.com', 'a.com');

        $this->assertSame(['a.com', 'b.com'], $route->getData('hosts'));
    }

    public function testImmutability(): void
    {
        $container = new SimpleContainer();
        $middlewareDispatcher = new MiddlewareDispatcher(
            new MiddlewareFactory($container),
        );

        $group = Group::create();

        $this->assertNotSame($group, $group->routes());
        $this->assertNotSame($group, $group->withDispatcher($middlewareDispatcher));
        $this->assertNotSame($group, $group->withCors(null));
        $this->assertNotSame($group, $group->middleware());
        $this->assertNotSame($group, $group->prependMiddleware());
        $this->assertNotSame($group, $group->namePrefix(''));
        $this->assertNotSame($group, $group->hosts());
        $this->assertNotSame($group, $group->disableMiddleware());
    }

    private function getRequestHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(404);
            }
        };
    }

    private function getDispatcher(): MiddlewareDispatcher
    {
        $container = new Container([]);
        return new MiddlewareDispatcher(
            new MiddlewareFactory($container),
            $this->createMock(EventDispatcherInterface::class)
        );
    }

    private function assertAllRoutesAndGroupsHaveDispatcher(array $items): void
    {
        $func = function ($item) use (&$func) {
            $this->assertTrue($item->getData('hasDispatcher'));
            if ($item instanceof Group) {
                $items = $item->getData('items');
                array_walk($items, $func);
            }
        };
        array_walk($items, $func);
    }
}
