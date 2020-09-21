<?php

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Router\MiddlewareDispatcher;
use Yiisoft\Router\MiddlewareFactory;
use Yiisoft\Router\MiddlewareStack;
use Yiisoft\Router\Tests\Support\Container;
use Yiisoft\Router\Tests\Support\TestController;

final class MiddlewareDispatcherTest extends TestCase
{
    public function testAddMiddleware(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $request = new ServerRequest('GET', '/');

        $dispatcher = $this->getDispatcher($container)->withMiddlewares([
            function () {
                return new Response(418);
            },
        ]);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(418, $response->getStatusCode());
    }

    public function testAddCallableMiddleware(): void
    {
        $request = new ServerRequest('GET', '/');

        $dispatcher = $this->getDispatcher()->withMiddlewares([
            static function (): ResponseInterface {
                return (new Response())->withStatus(418);
            },
        ]);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(418, $response->getStatusCode());
    }

    public function testAddCallableArrayMiddleware(): void
    {
        $request = new ServerRequest('GET', '/');
        $container = $this->getContainer([TestController::class => new TestController()]);
        $dispatcher = $this->getDispatcher($container)->withMiddlewares([[TestController::class, 'index']]);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testMiddlewareFullStackCalled(): void
    {
        $request = new ServerRequest('GET', '/');

        $middleware1 = function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            $request = $request->withAttribute('middleware', 'middleware1');
            return $handler->handle($request);
        };
        $middleware2 = function (ServerRequestInterface $request) {
            return new Response(200, [], null, '1.1', implode($request->getAttributes()));
        };

        $dispatcher = $this->getDispatcher()->withMiddlewares([$middleware2, $middleware1]);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('middleware1', $response->getReasonPhrase());
    }

    public function testMiddlewareStackInterrupted(): void
    {
        $request = new ServerRequest('GET', '/');

        $middleware1 = function () {
            return new Response(403);
        };
        $middleware2 = function () {
            return new Response(200);
        };

        $dispatcher = $this->getDispatcher()->withMiddlewares([$middleware2, $middleware1]);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(403, $response->getStatusCode());
    }

    public function testLazyLoadedArrayMiddlewareSuccessfulCall(): void
    {
        $request = new ServerRequest('GET', '/');
        $container = $this->getContainer([
            TestController::class => new TestController(),
        ]);
        $dispatcher = $this->getDispatcher($container)->withMiddlewares([[TestController::class, 'index']]);

        $response = $dispatcher->dispatch($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
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

    private function getDispatcher(ContainerInterface $container = null): MiddlewareDispatcher
    {
        if ($container === null) {
            return new MiddlewareDispatcher(new MiddlewareFactory($this->getContainer()), new MiddlewareStack());
        }

        return new MiddlewareDispatcher(new MiddlewareFactory($container), new MiddlewareStack());
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new Container($instances);
    }
}
