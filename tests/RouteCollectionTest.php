<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use InvalidArgumentException;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Router\Group;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\RouteNotFoundException;

final class RouteCollectionTest extends TestCase
{
    public function testAddRouteWithDuplicateName(): void
    {
        $listRoute = Route::get('/')->name('my-route');
        $viewRoute = Route::get('/{id}')->name('my-route');

        $group = Group::create()->routes($listRoute, $viewRoute);

        $collector = new RouteCollector();
        $collector->addGroup($group);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A route with name 'my-route' already exists.");
        $routeCollection = new RouteCollection($collector);
        $routeCollection->getRoutes();
    }

    public function testAddRouteWithDuplicateName2(): void
    {
        $listRoute = Route::get('/')->name('my-route');
        $viewRoute = Route::get('/{id}')->name('my-route');

        $group = Group::create()->routes($listRoute);

        $collector = new RouteCollector();
        $collector->addGroup($group);
        $collector->addRoute($viewRoute);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("A route with name 'my-route' already exists.");
        $routeCollection = new RouteCollection($collector);
        $routeCollection->getRoutes();
    }

    public function testRouteNotFound(): void
    {
        $listRoute = Route::get('/')->name('my-route');

        $group = Group::create()->routes($listRoute);

        $collector = new RouteCollector();
        $collector->addGroup($group);

        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionMessage('Cannot generate URI for route "wrong-name"; route not found');
        $routeCollection = new RouteCollection($collector);
        $routeCollection->getRoute('wrong-name');
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
        $this->assertSame('/{id}', $route->getData('pattern'));
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
        $this->assertFalse($route->getData('hasMiddlewares'));
    }

    public function testGetRouterTree(): void
    {
        $group1 = Group::create('/api')
            ->routes(
                Route::get('/test', $this->getDispatcher())->action(fn () => 2)->name('/test'),
                Route::get('/images/{sile}')->name('/image'),
                Group::create('/v1')
                    ->routes(
                        Route::get('/posts', $this->getDispatcher())->name('/posts'),
                        Route::get('/post/{sile}')->name('/post/view')
                    )->namePrefix('/v1')
            )->namePrefix('/api');

        $group2 = Group::create('/api')
            ->routes(
                Route::get('/posts', $this->getDispatcher())->name('/posts'),
                Route::get('/post/{sile}')->name('/post/view')
            )->namePrefix('/api');

        $collector = new RouteCollector();
        $collector->addGroup($group1);
        $collector->addGroup($group2);

        $routeCollection = new RouteCollection($collector);
        $routeTree = $routeCollection->getRouteTree();
        $this->assertCount(5, $routeTree);
        $this->assertArrayHasKey('/v1', $routeTree);
    }

    public function testGetRoutes(): void
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
        $routes = $routeCollection->getRoutes();
        $this->assertArrayHasKey('test', $routes);
        $this->assertArrayHasKey('image', $routes);
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
        $this->assertSame('https://yiiframework.com', $route1->getData('host'));
        $this->assertSame('https://yiipowered.com', $route2->getData('host'));
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

    public function testCollectorMiddlewareFullstackCalled(): void
    {
        $action = fn (ServerRequestInterface $request) => new Response(
            200,
            [],
            null,
            '1.1',
            implode($request->getAttributes())
        );
        $listRoute = Route::get('/')->action($action)->name('list');
        $viewRoute = Route::get('/{id}', $this->getDispatcher())->action($action)->name('view');

        $group = Group::create(null, $this->getDispatcher())->routes($listRoute);

        $middleware = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware1');
            return $handler->handle($request);
        };

        $collector = new RouteCollector();
        $collector->middleware($middleware);
        $collector->addGroup($group);
        $collector->addRoute($viewRoute);

        $routeCollection = new RouteCollection($collector);
        $route1 = $routeCollection->getRoute('list');
        $route2 = $routeCollection->getRoute('view');
        $request = new ServerRequest('GET', '/');
        $response1 = $route1->getData('dispatcherWithMiddlewares')->dispatch($request, $this->getRequestHandler());
        $response2 = $route2->getData('dispatcherWithMiddlewares')->dispatch($request, $this->getRequestHandler());

        $this->assertEquals('middleware1', $response1->getReasonPhrase());
        $this->assertEquals('middleware1', $response2->getReasonPhrase());
    }

    private function getRequestHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): Response
            {
                return new Response(404);
            }
        };
    }

    private function getDispatcher(): MiddlewareDispatcher
    {
        return new MiddlewareDispatcher(
            new MiddlewareFactory($this->createMock(ContainerInterface::class)),
            $this->createMock(EventDispatcherInterface::class)
        );
    }
}
