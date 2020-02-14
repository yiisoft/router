<?php

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\FastRoute\FastRouteFactory;
use Yiisoft\Router\Group;
use Yiisoft\Router\Middleware\Callback;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollectorInterface;
use Yiisoft\Router\Tests\Support\Container;

class GroupTest extends TestCase
{
    public function testAddRoute(): void
    {
        $listRoute = Route::get('/');
        $viewRoute = Route::get('/{id}');

        $group = new Group();
        $group->addRoute($listRoute);
        $group->addRoute($viewRoute);

        $this->assertCount(2, $group->getItems());
        $this->assertSame($listRoute, $group->getItems()[0]);
        $this->assertSame($viewRoute, $group->getItems()[1]);
    }

    public function testAddMiddleware(): void
    {
        $group = new Group();

        $middleware1 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $middleware2 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();

        $group
            ->addMiddleware($middleware1)
            ->addMiddleware($middleware2);

        $this->assertCount(2, $group->getMiddlewares());
        $this->assertSame($middleware1, $group->getMiddlewares()[1]);
        $this->assertSame($middleware2, $group->getMiddlewares()[0]);
    }

    public function testGroupMiddlewareFullStackCalled(): void
    {
        $factory = new FastRouteFactory();
        $router = $factory();
        $group = new Group('/group', function (RouteCollectorInterface $r) {
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

        $router->addGroup($group);
        $result = $router->match($request);
        $response = $result->process($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware1', $response->getReasonPhrase());
    }

    public function testGroupMiddlewareStackInterrupted(): void
    {
        $factory = new FastRouteFactory();
        $router = $factory();
        $group = new Group('/group', function (RouteCollectorInterface $r) {
            $r->addRoute(Route::get('/test1')->name('request1'));
        });

        $request = new ServerRequest('GET', '/group/test1');
        $middleware1 = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return new Response(403);
        }, $this->getContainer());
        $middleware2 = new Callback(function (ServerRequestInterface $request) {
            return new Response(200);
        }, $this->getContainer());

        $group->addMiddleware($middleware2)->addMiddleware($middleware1);

        $router->addGroup($group);
        $result = $router->match($request);
        $response = $result->process($request, $this->getRequestHandler());
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testAddGroup(): void
    {
        $logoutRoute = Route::post('/logout');
        $listRoute = Route::get('/');
        $viewRoute = Route::get('/{id}');

        $middleware1 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();
        $middleware2 = $this->getMockBuilder(MiddlewareInterface::class)->getMock();

        $root = new Group();
        $root->addGroup(new Group('/api', static function (Group $group) use ($logoutRoute, $listRoute, $viewRoute, $middleware1, $middleware2) {
            $group->addRoute($logoutRoute);
            $group->addGroup(new Group('/post', static function (Group $group) use ($listRoute, $viewRoute) {
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
        $this->assertSame($middleware1, $api->getMiddlewares()[1]);
        $this->assertSame($middleware2, $api->getMiddlewares()[0]);

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
        $this->assertSame($middleware1, $api->getMiddlewares()[1]);
        $this->assertSame($middleware2, $api->getMiddlewares()[0]);

        $this->assertSame('/post', $postGroup->getPrefix());
        $this->assertCount(2, $postGroup->getItems());
        $this->assertSame($listRoute, $postGroup->getItems()[0]);
        $this->assertSame($viewRoute, $postGroup->getItems()[1]);
        $this->assertEmpty($postGroup->getMiddlewares());
    }

    public function testContainerInjectedInRoutes(): void
    {
        $routes = [
            Route::get(''),
            Route::post(''),
            Route::delete(''),
            Route::patch(''),
            Route::put(''),
            Route::head(''),
            Route::options(''),
            Route::anyMethod(''),
        ];
        $group = new Group(
            '',
            static function (Group $group) use ($routes) {
                array_map(fn (Route $route) => $group->addRoute($route),$routes);
            },
            $this->getContainer()
        );

        array_map(fn (Route $route) => $this->assertFalse($route->hasContainer()), $routes);
        array_map(fn (Route $route) => $this->assertTrue($route->hasContainer()), $group->getItems());
    }

    public function testContainerInjectedInGroups(): void
    {
        $groups = [
            new Group(''),
            Group::create(''),
        ];
        $group = new Group(
            '/api',
            static function (Group $group) use ($groups) {
                array_map(fn (Group $item) => $group->addGroup($item), $groups);
            },
            $this->getContainer()
        );

        array_map(fn (Group $group) => $this->assertFalse($group->hasContainer()), $groups);
        array_map(fn (Group $group) => $this->assertTrue($group->hasContainer()), $group->getItems());
    }

    /**
     * @dataProvider getGroupsWithoutContainer()
     * @param \Yiisoft\Router\Group $group
     */
    public function testEmptyContainer(Group $group): void
    {
        $this->assertFalse($group->hasContainer());
    }

    /**
     * @dataProvider getGroupsWithContainer()
     * @param \Yiisoft\Router\Group $group
     */
    public function testHasContainer(Group $group): void
    {
        $this->assertTrue($group->hasContainer());
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

    public function getGroupsWithContainer(): array
    {
        return [
            [Group::create('/', [], $this->getContainer())],
            [new Group('/', fn () => [], $this->getContainer())],
        ];
    }

    public function getGroupsWithoutContainer(): array
    {
        return [
            [Group::create('/', [])],
            [new Group('/', fn () => [])],
        ];
    }
}
