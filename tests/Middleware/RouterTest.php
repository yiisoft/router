<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Middleware;

use LogicException;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\Group;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Router\Route;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\RouteCollector;
use Yiisoft\Router\UrlMatcherInterface;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class RouterTest extends TestCase
{
    public function testProcessSuccess(): void
    {
        $request = new ServerRequest('GET', '/');
        $response = $this->processWithRouter($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testMissingRouteRespondWith404(): void
    {
        $request = new ServerRequest('GET', '/no-such-route');
        $response = $this->processWithRouter($request);
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testMethodMismatchRespondWith405(): void
    {
        $request = new ServerRequest('POST', '/');
        $response = $this->processWithRouter($request);
        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, HEAD', $response->getHeaderLine('Allow'));
    }

    public function testAutoResponseOptions(): void
    {
        $request = new ServerRequest('OPTIONS', '/');
        $response = $this->processWithRouter($request);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('GET, HEAD', $response->getHeaderLine('Allow'));
    }

    public function testAutoResponseOptionsWithOrigin(): void
    {
        $request = new ServerRequest('OPTIONS', 'http://test.local/', ['Origin' => 'http://test.com']);
        $response = $this->processWithRouter($request);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('GET, HEAD', $response->getHeaderLine('Allow'));
    }

    public function testWithCorsHandlers(): void
    {
        $group = Group::create()->routes(
            Route::put('/post')->action(static fn () => new Response(204)),
            Route::post('/post')->action(static fn () => new Response(204)),
        )->withCors(
            static function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
                $response = $handler->handle($request);
                return $response->withHeader('Test', 'test from options handler');
            }
        );

        $collector = new RouteCollector();
        $collector->addGroup($group);
        $routeCollection = new RouteCollection($collector);

        $request = new ServerRequest('OPTIONS', '/post');
        $response = $this->processWithRouter($request, $routeCollection);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('test from options handler', $response->getHeaderLine('Test'));
        $request = new ServerRequest('POST', '/post');
        $response = $this->processWithRouter($request, $routeCollection);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('test from options handler', $response->getHeaderLine('Test'));
        $request = new ServerRequest('PUT', '/post');
        $response = $this->processWithRouter($request, $routeCollection);
        $this->assertSame(204, $response->getStatusCode());
        $this->assertSame('test from options handler', $response->getHeaderLine('Test'));
    }

    public function testWithCorsHandler(): void
    {
        $request = new ServerRequest('OPTIONS', '/options');
        $response = $this->processWithRouter($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testGetCurrentRoute(): void
    {
        $currentRoute = new CurrentRoute();
        $request = new ServerRequest('GET', '/');

        $this->processWithRouter($request, null, $currentRoute);

        $this->assertEquals('GET /', $currentRoute->getName());
    }

    public function testGetCurrentUri(): void
    {
        $currentRoute = new CurrentRoute();
        $request = new ServerRequest('GET', '/');

        $this->processWithRouter($request, null, $currentRoute);

        $this->assertSame($request->getUri(), $currentRoute->getUri());
    }

    public function testGetArguments(): void
    {
        $currentRoute = new CurrentRoute();
        $request = new ServerRequest('GET', '/');

        $this->processWithRouter($request, null, $currentRoute);

        $this->assertSame(['parameter' => 'value'], $currentRoute->getArguments());
    }

    public function testMiddlewareDispatcherWithMiddlewares(): void
    {
        $dispatcher = (new MiddlewareDispatcher(
            new MiddlewareFactory(new SimpleContainer()),
            $this->createMock(EventDispatcherInterface::class)
        ))->withMiddlewares([static fn () => new Response()]);

        $matcher = $this->getMatcher(new RouteCollection(new RouteCollector()));
        $responseFactory = new Psr17Factory();
        $currentRoute = new CurrentRoute();

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Middleware dispatcher must not contain middlewares for using in router.');
        new Router($matcher, $responseFactory, $dispatcher, $currentRoute);
    }

    private function getMatcher(?RouteCollectionInterface $routeCollection = null): UrlMatcherInterface
    {
        $middleware = $this->createRouteMiddleware();

        return new class ($middleware, $routeCollection) implements UrlMatcherInterface {
            private $middleware;
            private ?RouteCollectionInterface $routeCollection;

            public function __construct($middleware, ?RouteCollectionInterface $routeCollection = null)
            {
                $this->middleware = $middleware;
                $this->routeCollection = $routeCollection;
            }

            /**
             * Emulates router with a single `GET /` route
             *
             * @param ServerRequestInterface $request
             *
             * @return MatchingResult
             */
            public function match(ServerRequestInterface $request): MatchingResult
            {
                if ($this->routeCollection !== null) {
                    $route = $this->routeCollection->getRoute(
                        $request->getMethod() . ' ' . $request->getUri()->getPath()
                    );
                    return MatchingResult::fromSuccess($route, ['parameter' => 'value']);
                }
                if ($request->getMethod() === Method::OPTIONS && $request->getUri()->getPath() === '/options') {
                    $route = Route::options('/options')->middleware($this->middleware);
                    return MatchingResult::fromSuccess($route, ['method' => 'options']);
                }

                if ($request->getUri()->getPath() !== '/') {
                    return MatchingResult::fromFailure(Method::ALL);
                }

                if ($request->getMethod() === Method::GET) {
                    $route = Route::get('/')->middleware($this->middleware);
                    return MatchingResult::fromSuccess($route, ['parameter' => 'value']);
                }

                return MatchingResult::fromFailure([Method::GET, Method::HEAD]);
            }
        };
    }

    private function createResponseFactory(): ResponseFactoryInterface
    {
        return new class () implements ResponseFactoryInterface {
            public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
            {
                return new Response($code, [], null, '1.1', $reasonPhrase);
            }
        };
    }

    private function createRouterMiddleware(
        ?RouteCollectionInterface $routeCollection = null,
        ?CurrentRoute $currentRoute = null
    ): Router {
        $container = new SimpleContainer([ResponseFactoryInterface::class => $this->createResponseFactory()]);
        $dispatcher = new MiddlewareDispatcher(
            new MiddlewareFactory($container),
            $this->createMock(EventDispatcherInterface::class)
        );

        return new Router(
            $this->getMatcher($routeCollection),
            new Psr17Factory(),
            $dispatcher,
            $currentRoute ?? new CurrentRoute()
        );
    }

    private function processWithRouter(
        ServerRequestInterface $request,
        ?RouteCollectionInterface $routes = null,
        ?CurrentRoute $currentRoute = null
    ): ResponseInterface {
        return $this->createRouterMiddleware($routes, $currentRoute)->process($request, $this->createRequestHandler());
    }

    private function createRequestHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(404);
            }
        };
    }

    private function createRouteMiddleware(): callable
    {
        return static function () {
            return new Response(201);
        };
    }
}
