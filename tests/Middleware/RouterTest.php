<?php
namespace Yiisoft\Router\Tests\Middleware;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Method;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Router\Route;
use Yiisoft\Router\UrlMatcherInterface;

class RouterTest extends TestCase
{
    public function testProcessSuccess(): void
    {
        $request = new ServerRequest('GET', '/');

        $middleware = new Router($this->getMatcher());
        $response = $middleware->process($request, $this->getRequestHandler());
        $this->assertSame(418, $response->getStatusCode());
    }

    public function testProcessFailure(): void
    {
        $request = new ServerRequest('POST', '/');

        $middleware = new Router($this->getMatcher());
        $response = $middleware->process($request, $this->getRequestHandler());
        $this->assertSame(404, $response->getStatusCode());
    }

    private function getMatcher(): UrlMatcherInterface
    {
        $middleware = $this->getRouteMiddleware();

        return new class($middleware) implements UrlMatcherInterface {
            private $middleware;

            public function __construct(MiddlewareInterface $middleware)
            {
                $this->middleware = $middleware;
            }

            public function match(ServerRequestInterface $request): MatchingResult
            {
                if ($request->getMethod() === 'GET') {
                    $route = Route::get('/')->to($this->middleware);
                    return MatchingResult::fromSuccess($route, ['parameter' => 'value']);
                }

                return MatchingResult::fromFailure([Method::GET, Method::HEAD]);
            }
        };
    }

    private function getRequestHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(404);
            }
        };
    }

    private function getRouteMiddleware(): MiddlewareInterface
    {
        return new class implements MiddlewareInterface {
            public function process(
              ServerRequestInterface $request,
              RequestHandlerInterface $handler
            ): ResponseInterface {
                return (new Response())->withStatus(418);
            }
        };
    }
}
