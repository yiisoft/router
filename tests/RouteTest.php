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
use Yiisoft\Http\Method;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\MiddlewareFactory;
use Yiisoft\Router\Route;
use Yiisoft\Router\Tests\Support\Container;
use Yiisoft\Router\Tests\Support\TestController;

final class RouteTest extends TestCase
{
    public function testName(): void
    {
        $route = Route::get('/')->name('test.route');

        $this->assertSame('test.route', $route->getData('name'));
    }

    public function testNameDefault(): void
    {
        $route = Route::get('/');

        $this->assertSame('GET /', $route->getData('name'));
    }

    public function testMethods(): void
    {
        $route = Route::methods([Method::POST, Method::HEAD], '/');

        $this->assertSame([Method::POST, Method::HEAD], $route->getData('methods'));
    }

    public function testGetDataWithWrongKey(): void
    {
        $route = Route::get('');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown data key: wrong');

        $route->getData('wrong');
    }

    public function testGetMethod(): void
    {
        $route = Route::get('/');

        $this->assertSame([Method::GET], $route->getData('methods'));
    }

    public function testPostMethod(): void
    {
        $route = Route::post('/');

        $this->assertSame([Method::POST], $route->getData('methods'));
    }

    public function testPutMethod(): void
    {
        $route = Route::put('/');

        $this->assertSame([Method::PUT], $route->getData('methods'));
    }

    public function testDeleteMethod(): void
    {
        $route = Route::delete('/');

        $this->assertSame([Method::DELETE], $route->getData('methods'));
    }

    public function testPatchMethod(): void
    {
        $route = Route::patch('/');

        $this->assertSame([Method::PATCH], $route->getData('methods'));
    }

    public function testHeadMethod(): void
    {
        $route = Route::head('/');

        $this->assertSame([Method::HEAD], $route->getData('methods'));
    }

    public function testOptionsMethod(): void
    {
        $route = Route::options('/');

        $this->assertSame([Method::OPTIONS], $route->getData('methods'));
    }

    public function testPattern(): void
    {
        $route = Route::get('/test')->pattern('/test2');

        $this->assertSame('/test2', $route->getData('pattern'));
    }

    public function testHost(): void
    {
        $route = Route::get('/')->host('https://yiiframework.com/');

        $this->assertSame('https://yiiframework.com', $route->getData('host'));
    }

    public function testDefaults(): void
    {
        $route = Route::get('/{language}')->defaults(['language' => 'en']);

        $this->assertSame(['language' => 'en'], $route->getData('defaults'));
    }

    public function testOverride(): void
    {
        $route = Route::get('/')->override();

        $this->assertTrue($route->getData('override'));
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

    public function testDispatcherInjecting(): void
    {
        $request = new ServerRequest('GET', '/');
        $container = $this->getContainer(
            [
                TestController::class => new TestController(),
            ]
        );
        $dispatcher = $this->getDispatcher($container);
        $route = Route::get('/')->action([TestController::class, 'index']);
        $route->injectDispatcher($dispatcher);
        $response = $route->getDispatcherWithMiddlewares()->dispatch($request, $this->getRequestHandler());
        $this->assertSame(200, $response->getStatusCode());
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

    private function getDispatcher(ContainerInterface $container = null): MiddlewareDispatcher
    {
        if ($container === null) {
            return new MiddlewareDispatcher(
                new MiddlewareFactory($this->getContainer()),
                $this->createMock(EventDispatcherInterface::class)
            );
        }

        return new MiddlewareDispatcher(
            new MiddlewareFactory($container),
            $this->createMock(EventDispatcherInterface::class)
        );
    }

    private function getContainer(array $instances = []): ContainerInterface
    {
        return new Container($instances);
    }
}
