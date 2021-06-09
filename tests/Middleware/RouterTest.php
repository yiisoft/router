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
use Psr\Http\Message\UriInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Middleware\Dispatcher\MiddlewareStack;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Router\Route;
use Yiisoft\Router\UrlMatcherInterface;

final class RouterTest extends TestCase
{
    private function createRouterMiddleware(): Router
    {
        $container = $this->createMock(ContainerInterface::class);
        $dispatcher = new MiddlewareDispatcher(
            new MiddlewareFactory($container),
            $this->createMock(EventDispatcherInterface::class)
        );

        return new Router($this->getMatcher(), new Psr17Factory(), $dispatcher);
    }

    private function processWithRouter(ServerRequestInterface $request): ResponseInterface
    {
        return $this->createRouterMiddleware()->process($request, $this->createRequestHandler());
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

    private function getMatcher(): UrlMatcherInterface
    {
        $middleware = $this->createRouteMiddleware();

        return new class($middleware) implements UrlMatcherInterface {
            private $middleware;

            public function __construct($middleware)
            {
                $this->middleware = $middleware;
            }

            public function getCurrentRoute(): ?Route
            {
            }

            public function getCurrentUri(): ?UriInterface
            {
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
                if ($request->getUri()->getPath() !== '/') {
                    return MatchingResult::fromFailure(Method::ANY);
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
        return new class() implements RequestHandlerInterface {
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
