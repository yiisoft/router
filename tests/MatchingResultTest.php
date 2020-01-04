<?php

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Http\Method;
use Yiisoft\Router\Route;

final class MatchingResultTest extends TestCase
{
    public function testFromSucess(): void
    {
        $route = Route::get('/{name}');

        $result = MatchingResult::fromSuccess($route, ['name' => 'Mehdi']);
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['name' => 'Mehdi'], $result->parameters());
    }

    public function testFromFailureOnMethodFailure(): void
    {
        $result = MatchingResult::fromFailure([Method::GET, Method::HEAD]);

        $this->assertFalse($result->isSuccess());
        $this->assertTrue($result->isMethodFailure());
        $this->assertSame([Method::GET, Method::HEAD], $result->methods());
    }

    public function testFromFailureOnNotFoundFailure(): void
    {
        $result = MatchingResult::fromFailure(Method::ANY);

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isMethodFailure());
    }

    public function testProcessSuccess(): void
    {
        $route = Route::post('/')->to($this->getMiddleware());
        $result = MatchingResult::fromSuccess($route, []);
        $request = new ServerRequest('POST', '/');

        $response = $result->process($request, $this->getRequestHandler());
        $this->assertSame(201, $response->getStatusCode());
    }

    public function testProcessFailure(): void
    {
        $request = new ServerRequest('POST', '/');

        $response = MatchingResult::fromFailure([Method::GET, Method::HEAD])
            ->process($request, $this->getRequestHandler());

        $this->assertSame(404, $response->getStatusCode());
    }

    private function getMiddleware(): MiddlewareInterface
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

    private function getRequestHandler(): RequestHandlerInterface
    {
        return new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(404);
            }
        };
    }
}
