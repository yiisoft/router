<?php

namespace Yiisoft\Router\Tests\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\Factory\Psr17Factory;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Http\Method;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Router\Route;
use Yiisoft\Router\UrlMatcherInterface;

final class RouterTest extends TestCase
{
    private function createRouterMiddleware(): Router
    {
        return new Router($this->getMatcher(), new Psr17Factory());
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

        return new class ($middleware) implements UrlMatcherInterface {
            private $middleware;

            public function __construct(MiddlewareInterface $middleware)
            {
                $this->middleware = $middleware;
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
                    $route = Route::get('/')->to($this->middleware);
                    return MatchingResult::fromSuccess($route, ['parameter' => 'value']);
                }

                return MatchingResult::fromFailure([Method::GET, Method::HEAD]);
            }
        };
    }

    private function createRequestHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(404);
            }
        };
    }

    private function createRouteMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(
                ServerRequestInterface $request,
                RequestHandlerInterface $handler
            ): ResponseInterface {
                return (new Response())->withStatus(201);
            }
        };
    }
}
