<?php

declare(strict_types=1);

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use RuntimeException;
use Yiisoft\Http\Method;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Router\MatchingResult;
use Yiisoft\Router\Route;
use Yiisoft\Test\Support\Container\SimpleContainer;

final class MatchingResultTest extends TestCase
{
    public function testFromSuccess(): void
    {
        $route = Route::get('/{name}');

        $result = MatchingResult::fromSuccess($route, ['name' => 'Mehdi']);
        $this->assertTrue($result->isSuccess());
        $this->assertSame(['name' => 'Mehdi'], $result->arguments());
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
        $result = MatchingResult::fromFailure(Method::ALL);

        $this->assertFalse($result->isSuccess());
        $this->assertFalse($result->isMethodFailure());
    }

    public function testProcessSuccess(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $dispatcher = new MiddlewareDispatcher(
            new MiddlewareFactory($container),
            $this->createMock(EventDispatcherInterface::class)
        );
        $route = Route::post('/')->middleware($this->getMiddleware());
        $result = MatchingResult::fromSuccess($route, [])->withDispatcher($dispatcher);
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

    public function testRouteOnFailure(): void
    {
        $result = MatchingResult::fromFailure([Method::GET, Method::HEAD]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('There is no route in the matching result.');
        $result->route();
    }

    public function testImmutability(): void
    {
        $container = new SimpleContainer();
        $middlewareDispatcher = new MiddlewareDispatcher(
            new MiddlewareFactory($container),
        );

        $result = MatchingResult::fromFailure([Method::GET]);

        $this->assertNotSame($result, $result->withDispatcher($middlewareDispatcher));
    }

    private function getMiddleware(): callable
    {
        return static fn () => new Response(201);
    }

    private function getRequestHandler(): RequestHandlerInterface
    {
        return new class () implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new Response(404);
            }
        };
    }
}
