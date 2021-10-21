<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests\Middleware;

use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Router\Route;
use Yiisoft\Router\UrlMatcherInterface;

final class RouterTest extends TestCase
{
    private function createRouterMiddleware(?CurrentRoute $currentRoute = null): Router
    {
        $container = $this->createMock(ContainerInterface::class);
        $dispatcher = new MiddlewareDispatcher(
            new MiddlewareFactory($container),
            $this->createMock(EventDispatcherInterface::class)
        );

        return new Router($this->getMatcher(), new Psr17Factory(), $dispatcher, $currentRoute ?? new CurrentRoute());
    }

    private function processWithRouter(
        ServerRequestInterface $request,
        ?CurrentRoute $currentRoute = null
    ): ResponseInterface {
        return $this->createRouterMiddleware($currentRoute)->process($request, $this->createRequestHandler());
    }

    private function processWithRouterWithoutAutoResponse(
        ServerRequestInterface $request,
        ?CurrentRoute $currentRoute = null
    ): ResponseInterface {
        return $this->createRouterMiddleware($currentRoute)->withoutAutoResponseOptions()->process(
            $request,
            $this->createRequestHandler()
        );
    }

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

    public function testWithOptionsHandler(): void
    {
        $request = new ServerRequest('OPTIONS', '/options');
        $response = $this->processWithRouter($request);
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testWithoutAutoResponseOptions(): void
    {
        $request = new ServerRequest('OPTIONS', '/');
        $response = $this->processWithRouterWithoutAutoResponse($request);
        $this->assertSame(405, $response->getStatusCode());
        $this->assertSame('GET, HEAD', $response->getHeaderLine('Allow'));
    }

    public function testGetCurrentRoute(): void
    {
        $currentRoute = new CurrentRoute();
        $request = new ServerRequest('GET', '/');

        $this->processWithRouter($request, $currentRoute);

        $this->assertInstanceOf(Route::class, $currentRoute->getRoute());
        $this->assertEquals('GET /', $currentRoute->getRoute()->getName());
    }

    public function testGetCurrentUri(): void
    {
        $currentRoute = new CurrentRoute();
        $request = new ServerRequest('GET', '/');

        $this->processWithRouter($request, $currentRoute);

        $this->assertSame($request->getUri(), $currentRoute->getUri());
    }

    private function getMatcher(): UrlMatcherInterface
    {
        $middleware = $this->createRouteMiddleware();

        return new class ($middleware) implements UrlMatcherInterface {
            private $middleware;

            public function __construct($middleware)
            {
                $this->middleware = $middleware;
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
                if ($request->getMethod() === 'OPTIONS' && $request->getUri()->getPath() === '/options') {
                    $route = Route::methods(['OPTIONS'], '/options')->middleware($this->middleware);
                    return MatchingResult::fromSuccess($route, ['method' => 'options']);
                }

                if ($request->getUri()->getPath() !== '/') {
                    return MatchingResult::fromFailure(Method::ALL);
                }

                if ($request->getMethod() === 'GET') {
                    $route = Route::get('/')->middleware($this->middleware);
                    return MatchingResult::fromSuccess($route, ['parameter' => 'value']);
                }

                return MatchingResult::fromFailure([Method::GET, Method::HEAD]);
            }
        };
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
