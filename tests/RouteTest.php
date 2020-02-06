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
use Yiisoft\Http\Method;
use Yiisoft\Router\RouterFactory;
use Yiisoft\Router\RouterInterface;
use Yiisoft\Router\Tests\Support\Container;
use Yiisoft\Router\Tests\Support\TestController;
use Yiisoft\Router\Tests\Support\TestMiddleware;
use Yiisoft\Router\Middleware\Callback;
use Yiisoft\Router\Route;
use Yiisoft\Router\Group;
use Yiisoft\Router\MatchingResult;

final class RouteTest extends TestCase
{
    public function testName(): void
    {
        $route = Route::get('/')->name('test.route');

        $this->assertSame('test.route', $route->getName());
    }

    public function testNameDefault(): void
    {
        $route = Route::get('/');

        $this->assertSame('GET /', $route->getName());
    }

    public function testMethods(): void
    {
        $route = Route::methods([Method::POST, Method::HEAD], '/');

        $this->assertSame([Method::POST, Method::HEAD], $route->getMethods());
    }

    public const PATCH = 'PATCH';
    public const HEAD = 'HEAD';
    public const OPTIONS = 'OPTIONS';

    public function testGetMethod(): void
    {
        $route = Route::get('/');

        $this->assertSame([Method::GET], $route->getMethods());
    }

    public function testPostMethod(): void
    {
        $route = Route::post('/');

        $this->assertSame([Method::POST], $route->getMethods());
    }

    public function testPutMethod(): void
    {
        $route = Route::put('/');

        $this->assertSame([Method::PUT], $route->getMethods());
    }

    public function testDeleteMethod(): void
    {
        $route = Route::delete('/');

        $this->assertSame([Method::DELETE], $route->getMethods());
    }

    public function testPatchMethod(): void
    {
        $route = Route::patch('/');

        $this->assertSame([Method::PATCH], $route->getMethods());
    }

    public function testHeadMethod(): void
    {
        $route = Route::head('/');

        $this->assertSame([Method::HEAD], $route->getMethods());
    }

    public function testOptionsMethod(): void
    {
        $route = Route::options('/');

        $this->assertSame([Method::OPTIONS], $route->getMethods());
    }

    public function testAnyMethod(): void
    {
        $route = Route::anyMethod('/');

        $this->assertSame(Method::ANY, $route->getMethods());
    }

    public function testPattern(): void
    {
        $route = Route::get('/test')->pattern('/test2');

        $this->assertSame('/test2', $route->getPattern());
    }

    public function testHost(): void
    {
        $route = Route::get('/')->host('https://yiiframework.com/');

        $this->assertSame('https://yiiframework.com', $route->getHost());
    }

    public function testDefaults(): void
    {
        $route = Route::get('/{language}')->defaults(['language' => 'en']);

        $this->assertSame(['language' => 'en'], $route->getDefaults());
    }

    public function testToString(): void
    {
        $route = Route::methods([Method::GET, Method::POST], '/')->name('test.route')->host('yiiframework.com');

        $this->assertSame('[test.route] GET,POST yiiframework.com/', (string)$route);
    }

    public function testToStringSimple(): void
    {
        $route = Route::get('/');

        $this->assertSame('GET /', (string)$route);
    }

    public function testInvalidMiddlewareMethod(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Route::get('/', new \stdClass());
    }

    public function testInvalidMiddlewareAdd(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Route::get('/')->addMiddleware(new \stdClass());
    }

    public function testAddMiddleware(): void
    {
        $request = new ServerRequest('GET', '/');

        $route = Route::get('/')->addMiddleware(
            new class() implements MiddlewareInterface {
                public function process(
                    ServerRequestInterface $request,
                    RequestHandlerInterface $handler
                ): ResponseInterface {
                    return (new Response())->withStatus(418);
                }
            }
        );

        $response = $route->process($request, $this->getRequestHandler());
        $this->assertSame(418, $response->getStatusCode());
    }

    public function testAddCallableMiddleware(): void
    {
        $request = new ServerRequest('GET', '/');

        $route = Route::get('/', null, $this->getContainer())->addMiddleware(
            static function (): ResponseInterface {
                return (new Response())->withStatus(418);
            }
        );

        $response = $route->process($request, $this->getRequestHandler());
        $this->assertSame(418, $response->getStatusCode());
    }

    public function testAddCallableArrayMiddleware(): void
    {
        $request = new ServerRequest('GET', '/');

        $controller = new TestController();
        $route = Route::get('/', null, $this->getContainer())->addMiddleware([$controller, 'index']);

        $response = $route->process($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMiddlewareFullStackCalled(): void
    {
        $container = $this->getContainer();
        $request = new ServerRequest('GET', '/');

        $routeOne = Route::get('/');

        $middleware1 = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware1');
            return $handler->handle($request);
        }, $container);
        $middleware2 = new Callback(function (ServerRequestInterface $request) {
            return new Response(200, [], null, '1.1', implode($request->getAttributes()));
        }, $container);

        $routeOne = $routeOne->addMiddleware($middleware2)->addMiddleware($middleware1);

        $response = $routeOne->process($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware1', $response->getReasonPhrase());
    }

    public function testMiddlewareStackInterrupted(): void
    {
        $container = $this->getContainer();
        $request = new ServerRequest('GET', '/');

        $routeTwo = Route::get('/');

        $middleware1 = new Callback(function () {
            return new Response(403);
        }, $container);
        $middleware2 = new Callback(function () {
            return new Response(200);
        }, $container);

        $routeTwo = $routeTwo->addMiddleware($middleware2)->addMiddleware($middleware1);

        $response = $routeTwo->process($request, $this->getRequestHandler());
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testInvalidMiddlewareAddWrongStringLL(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Route::get('/', 'test');
    }

    public function testInvalidMiddlewareAddWrongStringClassLL(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Parameter should be either PSR middleware instance, PSR middleware class name, handler action or a callable.');
        Route::get('/', TestController::class);
    }

    public function testMiddlewareAddSuccessStringLL(): void
    {
        $route = Route::get('/', TestMiddleware::class, $this->getContainer());
        $this->assertInstanceOf(Route::class, $route);
    }

    public function testInvalidMiddlewareAddWrongArraySizeLL(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Route::get('/', ['test']);
    }

    public function testInvalidMiddlewareAddWrongArrayClassLL(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Route::get('/', ['class', 'test']);
    }

    public function testInvalidMiddlewareAddWrongArrayTypeLL(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Route::get('/', ['class' => TestController::class, 'index']);
    }

    public function testMiddlewareAddSuccessArrayLL(): void
    {
        $route = Route::get('/', [TestController::class, 'index'], $this->getContainer());
        $this->assertInstanceOf(Route::class, $route);
    }

    public function testMiddlewareCallSuccessArrayLL(): void
    {
        $request = new ServerRequest('GET', '/');
        $container = $this->getContainer([
            TestController::class => new TestController(),
        ]);
        $route = Route::get('/', [TestController::class, 'index'], $container);
        $response = $route->process($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMiddlewareCallSuccessArrayWithoutContainerLL(): void
    {
        $request = new ServerRequest('GET', '/');
        $container = $this->getContainer([
            TestController::class => new TestController(),
        ]);
        $route = Route::get('/', [TestController::class, 'index']);
        $route = $route->withContainer($container);
        $response = $route->process($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testContainerAutoInjectionByGroupFactory()
    {
        $container = $this->getContainer();

        $apiGroup = Group::create('/api', [
            Route::get('/info')->name('api-info'),
            Group::create('/v2', [
                Route::get('/user')->name('api-v2-user/index'),
                Route::get('/user/{id}')->name('api-v2-user/view'),
                Group::create('/news', [
                    Route::get('/post')->name('api-v2-news-post/index'),
                    Route::get('/post/{id}')->name('api-v2-news-post/view'),
                ]),
                Group::create('/blog', [
                    Route::get('/post')->name('api-v2-blog-post/index'),
                    Route::get('/post/{id}')->name('api-v2-blog-post/view'),
                ]),
                Route::get('/note')->name('api-v2-note/index'),
                Route::get('/note/{id}')->name('api-v2-note/view'),
            ]),
            Group::create('/v2', [
                Route::get('/user')->name('api-v2-user/index'),
                Route::get('/user/{id}')->name('api-v2-user/view'),
                Group::create('/news', [
                    Route::get('/post')->name('api-v2-news-post/index'),
                    Route::get('/post/{id}')->name('api-v2-news-post/view'),
                ]),
                Group::create('/blog', [
                    Route::get('/post')->name('api-v2-blog-post/index'),
                    Route::get('/post/{id}')->name('api-v2-blog-post/view'),
                ]),
                Route::get('/note')->name('api-v2-note/index'),
                Route::get('/note/{id}')->name('api-v2-note/view'),
            ])
        ], $container);

        $items = $apiGroup->getItems();

        $func = function ($item) use (&$func) {
            $this->assertSame(true, $item->hasContainer());
            if ($item instanceof Group) {
                $items = $item->getItems();
                array_walk($items, $func);
            }
        };
        array_walk($items, $func);
    }

    public function testContainerAutoInjectionByGroup()
    {
        $container = $this->getContainer();

        $apiGroup = new Group('/api', function (Group $group) {
            $group->addRoute(Route::get('/info')->name('api-info'));
            $group->addGroup(new Group('/v1', function (Group $group) {
                $group->addRoute(Route::get('/user')->name('api-v1-user/index'));
                $group->addRoute(Route::get('/user/{id}')->name('api-v1-user/view'));
                $group->addGroup(new Group('/news', function (Group $group) {
                    $group->addRoute(Route::get('/post')->name('api-v1-news-post/index'));
                    $group->addRoute(Route::get('/post/{id}')->name('api-v1-news-post/view'));
                }));
                $group->addGroup(new Group('/blog', function (Group $group) {
                    $group->addRoute(Route::get('/post')->name('api-v1-blog-post/index'));
                    $group->addRoute(Route::get('/post/{id}')->name('api-v1-blog-post/view'));
                }));
                $group->addRoute(Route::get('/note')->name('api-v1-note/index'));
                $group->addRoute(Route::get('/note/{id}')->name('api-v1-note/view'));
            }));
            $group->addGroup(new Group('/v2', function (Group $group) {
                $group->addRoute(Route::get('/user')->name('api-v2-user/index'));
                $group->addRoute(Route::get('/user/{id}')->name('api-v2-user/view'));
                $group->addGroup(new Group('/news', function (Group $group) {
                    $group->addRoute(Route::get('/post')->name('api-v2-news-post/index'));
                    $group->addRoute(Route::get('/post/{id}')->name('api-v2-news-post/view'));
                }));
                $group->addGroup(new Group('/blog', function (Group $group) {
                    $group->addRoute(Route::get('/post')->name('api-v2-blog-post/index'));
                    $group->addRoute(Route::get('/post/{id}')->name('api-v2-blog-post/view'));
                }));
                $group->addRoute(Route::get('/note')->name('api-v2-note/index'));
                $group->addRoute(Route::get('/note/{id}')->name('api-v2-note/view'));
            }));
        }, $container);

        $items = $apiGroup->getItems();

        $func = function ($item) use (&$func) {
            $this->assertSame(true, $item->hasContainer());
            if ($item instanceof Group) {
                $items = $item->getItems();
                array_walk($items, $func);
            }
        };
        array_walk($items, $func);
    }

    public function testContainerAutoInjectionByRouterFactory()
    {
        $container = $this->getContainer();

        $routes = [
            Route::get('/info')->name('api-info'),
            Group::create('/v2', [
                Route::get('/user')->name('api-v2-user/index'),
                Route::get('/user/{id}')->name('api-v2-user/view'),
                Group::create('/news', [
                    Route::get('/post')->name('api-v2-news-post/index'),
                    Route::get('/post/{id}')->name('api-v2-news-post/view'),
                ]),
                Group::create('/blog', [
                    Route::get('/post')->name('api-v2-blog-post/index'),
                    Route::get('/post/{id}')->name('api-v2-blog-post/view'),
                ]),
                Route::get('/note')->name('api-v2-note/index'),
                Route::get('/note/{id}')->name('api-v2-note/view'),
            ]),
            Group::create('/v2', [
                Route::get('/user')->name('api-v2-user/index'),
                Route::get('/user/{id}')->name('api-v2-user/view'),
                Group::create('/news', [
                    Route::get('/post')->name('api-v2-news-post/index'),
                    Route::get('/post/{id}')->name('api-v2-news-post/view'),
                ]),
                Group::create('/blog', [
                    Route::get('/post')->name('api-v2-blog-post/index'),
                    Route::get('/post/{id}')->name('api-v2-blog-post/view'),
                ]),
                Route::get('/note')->name('api-v2-note/index'),
                Route::get('/note/{id}')->name('api-v2-note/view'),
            ])
        ];

        $factory = new RouterFactory($this->getEngineFactory(), $routes);
        $router = $factory($container);
        $items = $router->getItems();

        $func = function ($item) use (&$func) {
            $this->assertSame(true, $item->hasContainer());
            if ($item instanceof Group) {
                $items = $item->getItems();
                array_walk($items, $func);
            }
        };
        array_walk($items, $func);
    }

    private function getEngineFactory(): callable
    {
        return new class() {
            public function __invoke()
            {
                return new class() extends Group implements RouterInterface {
                    public function match(ServerRequestInterface $request): MatchingResult
                    {
                    }

                    public function generate(string $name, array $parameters = []): string
                    {
                    }

                    public function getUriPrefix(): string
                    {
                    }

                    public function setUriPrefix(string $name): void
                    {
                    }
                };
            }
        };
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
}
