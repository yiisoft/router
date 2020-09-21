<?php

namespace Yiisoft\Router\Tests\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\MiddlewareDispatcher;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Http\Method;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Router\MiddlewareFactory;
use Yiisoft\Router\MiddlewareStack;
use Yiisoft\Router\Route;
use Yiisoft\Router\Group;
use Yiisoft\Router\RouteCollection;
use Yiisoft\Router\RouteCollectionInterface;
use Yiisoft\Router\UrlMatcherInterface;

final class RouterTest extends TestCase
{
    private function createRouterMiddleware(): Router
    {
        $container = $this->createMock(ContainerInterface::class);
        $dispatcher = new MiddlewareDispatcher(new MiddlewareFactory($container), new MiddlewareStack());
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

            public function getLastMatchedRequest(): ?ServerRequestInterface
            {
            }

            public function getRouteCollection(): RouteCollectionInterface
            {
                $collector = Group::create();
                return new RouteCollection($collector);
            }

            /**
             * Emulates router with a single `GET /` route
             * @param ServerRequestInterface $request
             * @return MatchingResult
             */
            public function match(ServerRequestInterface $request): MatchingResult
            {
                if ($request->getUri()->getPath() !== '/') {
                    return MatchingResult::fromFailure(Method::ANY);
                }

                if ($request->getMethod() === 'GET') {
                    $route = Route::get('/')->addMiddleware($this->middleware);
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
