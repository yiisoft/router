<?php

namespace Yiisoft\Router\Tests;

use Nyholm\Psr7\Response;
use Nyholm\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Method;
use Yiisoft\Router\Middleware\Callback;
use Yiisoft\Router\Route;

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

    public function testInvalidTo(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Route::get('/')->to(new \stdClass());
    }

    public function testToMiddleware(): void
    {
        $request = new ServerRequest('GET', '/');

        $route = Route::get('/')->to(
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

    public function testToCallable(): void
    {
        $request = new ServerRequest('GET', '/');

        $route = Route::get('/')->to(
            static function (): ResponseInterface {
                return (new Response())->withStatus(418);
            }
        );

        $response = $route->process($request, $this->getRequestHandler());
        $this->assertSame(418, $response->getStatusCode());
    }

    public function testThenByStatusCode(): void
    {
        $request = new ServerRequest('GET', '/');

        $routeOne = Route::get('/');

        $middleware1 = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        });
        $middleware2 = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return new Response(200);
        });

        $routeOne = $routeOne->to($middleware1)->then($middleware2);

        $response = $routeOne->process($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());

        $routeTwo = Route::get('/');

        $middleware1 = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return new Response(404);
        });
        $middleware2 = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return new Response(200);
        });

        $routeTwo = $routeTwo->to($middleware1)->then($middleware2);

        $response = $routeTwo->process($request, $this->getRequestHandler());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testThenByBody(): void
    {
        $request = new ServerRequest('GET', '/');

        $routeOne = Route::get('/');

        $middleware1 = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        });
        $middleware2 = new Callback(function () {
            $response = new Response(201);
            $response->getBody()->write('middleware2');
            return $response;
        });

        $routeOne = $routeOne->to($middleware1)->then($middleware2);

        $response = $routeOne->process($request, $this->getRequestHandler());
        $body = $response->getBody();
        $body->rewind();
        $this->assertSame('middleware2', $response->getBody()->getContents());

        $routeTwo = Route::get('/');

        $middleware1 = new Callback(function () {
            $response = new Response(201);
            $response->getBody()->write('middleware1');
            return $response;
        });
        $middleware2 = new Callback(function () {
            $response = new Response(201);
            $response->getBody()->write('middleware2');
            return $response;
        });

        $routeTwo = $routeTwo->to($middleware1)->then($middleware2);

        $response = $routeTwo->process($request, $this->getRequestHandler());
        $body = $response->getBody();
        $body->rewind();
        $this->assertSame('middleware1', $response->getBody()->getContents());
    }

    public function testBeforeByStatusCode(): void
    {
        $request = new ServerRequest('GET', '/');

        $routeOne = Route::get('/');

        $middleware1 = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return new Response(200);
        });
        $middleware2 = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        });

        $routeOne = $routeOne->to($middleware1)->prepend($middleware2);

        $response = $routeOne->process($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());

        $routeTwo = Route::get('/');

        $middleware1 = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return new Response(404);
        });
        $middleware2 = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return new Response(200);
        });

        $routeTwo = $routeTwo->to($middleware1)->then($middleware2);

        $response = $routeTwo->process($request, $this->getRequestHandler());
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testBeforeByBody(): void
    {
        $request = new ServerRequest('GET', '/');

        $routeOne = Route::get('/');

        $middleware1 = new Callback(function () {
            $response = new Response(201);
            $response->getBody()->write('middleware1');
            return $response;
        });
        $middleware2 = new Callback(function () {
            $response = new Response(201);
            $response->getBody()->write('middleware2');
            return $response;
        });

        $routeOne = $routeOne->to($middleware1)->prepend($middleware2);

        $response = $routeOne->process($request, $this->getRequestHandler());
        $body = $response->getBody();
        $body->rewind();
        $this->assertSame('middleware2', $response->getBody()->getContents());

        $middleware1 = new Callback(function () {
            $response = new Response(201);
            $response->getBody()->write('middleware1');
            return $response;
        });
        $middleware2 = new Callback(function (ServerRequestInterface $request, RequestHandlerInterface $handler) {
            return $handler->handle($request);
        });

        $routeTwo = Route::get('/');
        $routeTwo = $routeTwo->to($middleware1)->prepend($middleware2);

        $response = $routeTwo->process($request, $this->getRequestHandler());
        $body = $response->getBody();
        $body->rewind();
        $this->assertSame('middleware1', $response->getBody()->getContents());
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
}
