<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Builder;

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
use Yiisoft\Router\Builder\GroupBuilder as Group;
use Yiisoft\Router\Builder\RouteBuilder as Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\Tests\Support\Container;
use Yiisoft\Router\Tests\Support\TestMiddleware1;
use Yiisoft\Router\Tests\Support\TestMiddleware2;
use Yiisoft\Router\Tests\Support\TestMiddleware3;

final class GroupBuilderTest extends TestCase
{
    public function testAddMiddleware(): void
    {
        $group = Group::create();

        $middleware1 = static fn () => new Response();
        $middleware2 = static fn () => new Response();

        $group = $group
            ->middleware($middleware1)
            ->middleware($middleware2);
        $groupRoute = $group->toRoute();

        $this->assertCount(2, $groupRoute->getEnabledMiddlewares());
        $this->assertSame($middleware1, $groupRoute->getEnabledMiddlewares()[0]);
        $this->assertSame($middleware2, $groupRoute->getEnabledMiddlewares()[1]);
    }

    public function testMiddlewaresWithKeys(): void
    {
        $group = Group::create()
                      ->middleware(m3: TestMiddleware3::class)
                      ->prependMiddleware(m1: TestMiddleware1::class, m2: TestMiddleware2::class)
                      ->disableMiddleware(m1: TestMiddleware1::class);
        $groupRoute = $group->toRoute();

        $this->assertSame(
            [TestMiddleware2::class, TestMiddleware3::class],
            $groupRoute->getEnabledMiddlewares()
        );
    }

    public function testNamedArgumentsInMiddlewareMethods(): void
    {
        $group = Group::create()
                      ->middleware(TestMiddleware3::class)
                      ->prependMiddleware(TestMiddleware1::class, TestMiddleware2::class)
                      ->disableMiddleware(TestMiddleware1::class, TestMiddleware3::class);
        $groupRoute = $group->toRoute();

        $this->assertCount(1, $groupRoute->getEnabledMiddlewares());
        $this->assertSame(TestMiddleware2::class, $groupRoute->getEnabledMiddlewares()[0]);
    }

    public function testRoutesAfterMiddleware(): void
    {
        $group = Group::create();

        $middleware1 = static fn () => new Response();

        $group = $group->prependMiddleware($middleware1);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('routes() can not be used after prependMiddleware().');

        $group->routes(Route::get('/')->toRoute());
    }

    public function testAddNestedMiddleware(): void
    {
        $request = new ServerRequest('GET', '/outergroup/innergroup/test1');

        $action = static fn (ServerRequestInterface $request) => new Response(
            200,
            [],
            null,
            '1.1',
            implode('', $request->getAttributes())
        );

        $middleware1 = static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware1');
            return $handler->handle($request);
        };

        $middleware2 = static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware2');
            return $handler->handle($request);
        };

        $group = Group::create('/outergroup')
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
        $response = $this->getDispatcher()
                         ->withMiddlewares($route->getEnabledMiddlewares())
                         ->dispatch($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware2', $response->getReasonPhrase());
    }

    public function testGroupMiddlewareFullStackCalled(): void
    {
        $request = new ServerRequest('GET', '/group/test1');

        $action = static fn (ServerRequestInterface $request) => new Response(
            200,
            [],
            null,
            '1.1',
            implode('', $request->getAttributes())
        );
        $middleware1 = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware1');
            return $handler->handle($request);
        };
        $middleware2 = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware2');
            return $handler->handle($request);
        };

        $group = Group::create('/group')
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

        $response = $this->getDispatcher()
                         ->withMiddlewares($route->getEnabledMiddlewares())
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

        $group = Group::create('/group')
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

        $response = $this->getDispatcher()
                         ->withMiddlewares($route->getEnabledMiddlewares())
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
        $rootGroup = $root->toRoute();

        $this->assertCount(1, $rootGroup->getRoutes());

        /** @var Group $api */
        $api = $rootGroup->getRoutes()[0];
        $apiRoute = $api->toRoute();

        $this->assertSame('/api', $apiRoute->getPrefix());
        $this->assertCount(2, $apiRoute->getRoutes());
        $this->assertSame($logoutRoute, $apiRoute->getRoutes()[0]);

        /** @var Group $postGroup */
        $postGroup = $apiRoute->getRoutes()[1];
        $postGroup = $postGroup->toRoute();

        $this->assertInstanceOf(\Yiisoft\Router\Group::class, $postGroup);
        $this->assertCount(2, $apiRoute->getEnabledMiddlewares());
        $this->assertSame($middleware1, $apiRoute->getEnabledMiddlewares()[0]);
        $this->assertSame($middleware2, $apiRoute->getEnabledMiddlewares()[1]);

        $this->assertSame('/post', $postGroup->getPrefix());
        $this->assertCount(2, $postGroup->getRoutes());
        $this->assertSame($listRoute, $postGroup->getRoutes()[0]);
        $this->assertSame($viewRoute, $postGroup->getRoutes()[1]);
        $this->assertEmpty($postGroup->getEnabledMiddlewares());
    }

    public function testHost(): void
    {
        $group = Group::create()->host('https://yiiframework.com/');

        $this->assertSame('https://yiiframework.com', $group->toRoute()->getHosts()[0]);
    }

    public function testHosts(): void
    {
        $group = Group::create()->hosts('https://yiiframework.com/', 'https://yiiframework.ru/');

        $this->assertSame(['https://yiiframework.com', 'https://yiiframework.ru'], $group->toRoute()->getHosts());
    }

    public function testName(): void
    {
        $group = Group::create()->namePrefix('api');

        $this->assertSame('api', $group->toRoute()->getNamePrefix());
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

    public function testWithCorsWithHostRoutes(): void
    {
        $group = Group::create()
                      ->routes(
                          Route::get('/info')
                               ->action(static fn () => 'info')
                               ->host('yii.dev'),
                          Route::get('/info')
                               ->action(static fn () => 'info')
                               ->host('yii.test'),
                      )
                      ->withCors(
                          static fn () => new Response(204)
                      );

        $collector = new RouteCollector();
        $collector->addRoute($group);
        $routeCollection = new RouteCollection($collector);

        $this->assertCount(4, $routeCollection->getRoutes());
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
        $this->assertInstanceOf(\Yiisoft\Router\Route::class, $routeCollection->getRoute('OPTIONS /v1/post'));
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
        $this->assertInstanceOf(\Yiisoft\Router\Route::class, $routeCollection->getRoute('OPTIONS /v1/post'));
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

        $this->assertSame(['a.com', 'b.com'], $route->toRoute()->getHosts());
    }

    public function testImmutability(): void
    {
        $group = Group::create();

        $this->assertNotSame($group, $group->routes());
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
}
